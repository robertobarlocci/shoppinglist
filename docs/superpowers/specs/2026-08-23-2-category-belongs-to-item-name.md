# 2026-08-23 — #2 The category belongs to the item name, not to the last row created

## Goal

Stop the app from silently rewriting an item's category. Today, adding a known item name through
the Smart Input / Quick Buy / meal-planner paths writes a **default** category (`Sonstiges`) or a
**stale dropdown** value, and checking that row off copies it onto the surviving inventory row —
so the curated category is destroyed and the corrupted one becomes the new truth in the
autocomplete badge. Closes #2.

## Surgical scope

Limited to exactly the user's request: the six defects named in issue #2 (M1–M6), the eight
proposed fixes, and the seven acceptance criteria. Nothing else.

**Out of scope** (tempting, deliberately excluded):

- Household/user scoping of `findDuplicateInInventory` (pre-existing, single-household app).
- Rewriting `OfflineSyncService` to use `CreateItemAction` wholesale (it also logs activity
  differently and compares `list_type` against string constants — a separate bug). Only the
  category resolution is aligned.
- Introducing a JS test harness (vitest/@vue/test-utils) — the repo has none; frontend behaviour
  is verified by the §7 Playwright walk instead.
- Any redesign of the category picker UX beyond pre-fill + reset + in-flight guard.
- The `ItemController@update` duplicated docblock and other cosmetic defects.

## Principle

> **The category belongs to the item *name*, not to the row that happened to be created last.
> It may only be changed by an explicit user action — never by a default.**

This needs an *explicitness signal*, because the destructive write in `MoveItemAction` cannot
otherwise tell "the user deliberately re-categorised this" from "this is the `Sonstiges` fallback
nobody chose". We persist that signal on the row.

### `items.category_is_explicit` (new, boolean, default `false`)

| Path | Value | Why |
|---|---|---|
| `PUT /api/items/{item}` with `category_id` present (edit modal) | `true` | The edit modal is the unambiguous "I am choosing this category" gesture — including choosing `Sonstiges`. |
| `POST /api/items` with `category_id` present and **not** the `other` default | `true` | A non-default choice can only have come from the dropdown. |
| `POST /api/items` with `category_id` == the `other` default | `false` | Indistinguishable from the fallback (this is exactly the M1/M2 race). |
| `POST /api/items` with no `category_id` → inherited by name | `false` | Inherited, not chosen. |
| Offline sync create/update | same rules as above | Same resolver. |
| Meal-plan → shopping list | `false` | Derived from the linked item / name history. |
| Recurring trigger | copies the source row's flag | It is a copy of a curated row. |

`MoveItemAction::handleDuplicate` then overwrites the survivor's category **only** when the
incoming row carries `category_is_explicit = true`.

## What ships

### T1 — `ItemCategoryResolver` (new service) + `category_is_explicit` column

`app/Services/ItemCategoryResolver.php` centralises the three duplicated `'other'` lookups and
adds inherit-by-name:

```php
public function defaultCategoryId(): ?int;                       // slug 'other'
public function inheritedCategoryIdForName(string $name): ?int;  // case-insensitive, curated-first
public function resolveForNewItem(?int $categoryId, string $name): array{0:?int,1:bool}; // [id, isExplicit]
```

`inheritedCategoryIdForName` picks, among non-trashed items with `LOWER(name) = LOWER($name)` and a
non-null `category_id` that is not the `other` default:
`ORDER BY category_is_explicit DESC, (list_type = 'inventory') DESC, id DESC`.

Migration `..._add_category_is_explicit_to_items_table.php` — `boolean('category_is_explicit')
->default(false)` after `category_id`, plus `down()` dropping it.

### T2 — `CreateItemAction` inherits by name (M4)

```php
[$categoryId, $isExplicit] = $this->categoryResolver->resolveForNewItem(
    $data['category_id'] ?? null,
    $data['name'],
);
```

### T3 — `MoveItemAction` stops the destructive overwrite (M3) + deterministic survivor

```php
// Only a category the user explicitly chose may replace the curated one.
if ($item->category_is_explicit && $item->category_id !== null
    && $item->category_id !== $existingItem->category_id) {
    $existingItem->category_id = $item->category_id;
    $existingItem->category_is_explicit = true;
    $existingItem->save();
}
```

`findDuplicateInInventory` gains `->orderByDesc('category_is_explicit')->orderBy('id')` so the
survivor is deterministic and prefers the curated row.

### T4 — `suggest` returns the curated row, case-insensitively (M6)

Replace the `MAX(id)`/`groupBy('name')` subquery with a Postgres `DISTINCT ON (LOWER(name))`
that ranks inventory > to_buy > quick_buy, then explicit, then newest — and **bind the query
parameter** instead of interpolating it into the `ILIKE` string (existing SQL-injection surface
in `app/Http/Controllers/Api/ItemController.php:188`).

### T5 — `update` marks the choice explicit (edit modal)

`ItemController@update` and `OfflineSyncService@handleUpdateItem`: when the payload carries
`category_id`, also write `category_is_explicit = true`.

### T6 — meal-plan ingredients keep the link and the category (M5)

- `AddIngredientsToShoppingListAction::resolveCategoryId` → linked item's category, else
  inherit-by-name, else `other`; `itemExistsInShoppingList` becomes case-insensitive.
- `resources/js/Pages/MealPlanner.vue`: `selectIngredientSuggestion` keeps `suggestion.id`,
  `addIngredient` posts it as `item_id` (already accepted by `AddIngredientRequest`).

### T7 — Dashboard: pre-fill, reset, and close the debounce race (M1, M2, M4)

- `categoryTouchedByUser` ref + `@change` on the select.
- Watcher on `suggestions`: when one matches `searchQuery` case-insensitively and the user has not
  touched the dropdown for this input, set `selectedCategoryForNewItem` to that suggestion's
  category.
- `isSearching` ref: `true` from the input event until the debounced request resolves;
  `canAddNewItem` requires `!isSearching`.
- `resetNewItemCategory()` after every successful add.
- `addQuickBuy` stops sending `category_id` (backend inherits).
- `addNewItem` sends the trimmed name.

### T8 — reversible data repair

Migration `..._repair_item_categories.php`:

- creates `item_category_repairs (id, item_id, previous_category_id, created_at)`
- for every non-trashed item whose category is `other` or `null`, finds a donor category from
  another row with the same name (case-insensitive, including trashed) whose category is neither
  `other` nor null, preferring explicit → inventory → newest; updates and logs the previous value
- `down()` restores every logged row and drops the table.

## Teilaufgaben

1. **T1** — `ItemCategoryResolver` + `category_is_explicit` migration + `Item` fillable/casts.
2. **T2** — `CreateItemAction` uses the resolver.
3. **T3** — `MoveItemAction`: explicitness guard + deterministic survivor.
4. **T4** — `ItemController@suggest`: `DISTINCT ON (LOWER(name))`, curated-first, bound parameter.
5. **T5** — `update` paths mark the category explicit.
6. **T6** — meal-plan ingredient category (backend + `MealPlanner.vue` + `mealPlans.js`).
7. **T7** — `Dashboard.vue` pre-fill / reset / in-flight guard / Quick Buy.
8. **T8** — reversible data-repair migration.

Each is finished, tested and green before the next starts.

## Edge cases

- `other` category missing (never seeded) → resolver returns `null`; behaviour unchanged from today.
- Item name differing only by case (`Cola` / `cola`) → one suggestion, one dedup target.
- Explicit category equal to the survivor's → no write, no activity noise.
- Recurring items never reach `handleDuplicate` (they are completed instead) — unaffected.
- Offline-created rows with `category_id = null` → resolver fills them on sync; `null` survives
  only when `other` is unseeded.
- Data repair with no donor row → item left untouched, nothing logged.
- Data repair run twice → the second run finds nothing to change (repaired rows are no longer
  `other`/null).

## Threat model

- **Auth surface:** unchanged. `StoreItemRequest::authorize()` (parent only) and
  `UpdateItemRequest::authorize()` (`can('update', $item)`) are untouched. `category_is_explicit`
  is **never** accepted from the request — it is derived server-side, so a client cannot forge
  "the user chose this" and hijack another row's category through the dedup path.
- **Untrusted input:** `q` on `/api/items/suggest` was interpolated into a raw `ILIKE` string.
  This change binds it. `name` used by the resolver and the repair migration is bound in every
  query (`whereRaw('LOWER(name) = ?', [...])`).
- **Data sensitivity:** none (grocery names). Single-household app.
- **Blast radius if abused:** the data-repair migration rewrites `category_id` on existing rows;
  it is bounded to rows currently on `other`/`null`, logs every previous value, and is reversible.

## Tests (each becomes a feature test)

1. `MoveItemAction`: to_buy `Cola` (`other`, non-explicit) checked off against inventory `Cola`
   (`Getränke`) → inventory keeps **Getränke**.
2. `MoveItemAction`: to_buy `Cola` with `category_is_explicit = true` (`Zucker`) → inventory
   adopts **Zucker** and becomes explicit.
3. `MoveItemAction`: to_buy `Cola` non-explicit but with a *non-default* category → inventory
   still keeps its curated category (only explicit wins).
4. `MoveItemAction`: two inventory rows of the same name → deterministic survivor (explicit first,
   then oldest).
5. `CreateItemAction`: create `Cola` with no `category_id` while `Cola`/`Getränke` exists →
   inherits **Getränke**, `category_is_explicit = false`.
6. `CreateItemAction`: create an unknown name with no `category_id` → falls back to `other`.
7. `CreateItemAction`: create with an explicit non-default `category_id` → `category_is_explicit = true`.
8. `CreateItemAction`: create with `category_id` == `other` → `category_is_explicit = false`.
9. `POST /api/items`: `category_is_explicit` in the request body is ignored (never mass-assigned).
10. `PUT /api/items/{id}` with `category_id` → `category_is_explicit = true`.
11. `ItemController@suggest`: `cola` and `Cola` collapse to one suggestion.
12. `ItemController@suggest`: returns the **inventory** row's category, not the newest row's.
13. `ItemController@suggest`: a `%`/`_` in `q` is treated literally, not as a wildcard injection.
14. `AddIngredientsToShoppingListAction`: ingredient linked via `item_id` → shopping-list item gets
    that item's category.
15. `AddIngredientsToShoppingListAction`: unlinked ingredient whose name is known → inherits that
    name's category.
16. `AddIngredientsToShoppingListAction`: existing `to_buy` row matched case-insensitively → no
    duplicate created.
17. `OfflineSyncService`: offline create with no category → inherits by name.
18. **End-to-end regression for issue #2**: inventory `Cola`/`Getränke` → create `Cola` in `to_buy`
    through `POST /api/items` with the *default* category (the race) → `POST .../move` to inventory
    → inventory row still **Getränke**, and `suggest` reports **Getränke**.
19. Data repair migration: an item on `Sonstiges` with a `Getränke` sibling of the same name is
    repaired; `down()` restores `Sonstiges`.

## Files changed

| File | Change |
|---|---|
| `app/Services/ItemCategoryResolver.php` | **new** — default / inherit-by-name / explicitness |
| `database/migrations/..._add_category_is_explicit_to_items_table.php` | **new** — column + rollback |
| `database/migrations/..._repair_item_categories.php` | **new** — reversible data repair |
| `app/Models/Item.php` | fillable + bool cast for `category_is_explicit` |
| `app/Actions/Item/CreateItemAction.php` | use the resolver (M4) |
| `app/Actions/Item/MoveItemAction.php` | explicitness guard (M3) + deterministic survivor |
| `app/Http/Controllers/Api/ItemController.php` | `suggest` rewrite (M6) + `update` marks explicit |
| `app/Services/OfflineSyncService.php` | create/update use the resolver |
| `app/Services/RecurringService.php` | copy the source row's explicitness |
| `app/Actions/MealPlan/AddIngredientsToShoppingListAction.php` | inherit-by-name + case-insensitive dedup (M5) |
| `resources/js/Pages/Dashboard.vue` | pre-fill, reset, in-flight guard, Quick Buy (M1, M2, M4) |
| `resources/js/Pages/MealPlanner.vue` | keep `item_id` on suggestion select (M5) |
| `docker/nginx/default.conf` | `if_not_empty` on forwarded fastcgi params (dev stack 500s without it) |
| `tests/Feature/ItemCategoryTest.php` | **new** — tests 1–13, 17, 18 |
| `tests/Feature/MealPlanTest.php` | tests 14–16 |
| `tests/Feature/ItemCategoryRepairTest.php` | **new** — test 19 |

## Risks

- **`DISTINCT ON` is Postgres-only.** Accepted: the repo is Postgres-only by directive and the
  existing query already used `ILIKE`.
- **The migration rewrites existing data.** Mitigated by the log table and a working `down()`.
- **A user who explicitly wants `Sonstiges` on a *new* item** will not have that stick through the
  dedup path (it is indistinguishable from the fallback). They can set it in the edit modal, which
  is the explicit path. Documented above and in the code comment.

## Acceptance checklist

- [ ] Adding a known item name through autocomplete keeps its existing category.
- [ ] The dropdown pre-fills with the known category and resets to the default after each add.
- [ ] Checking off never changes the inventory category unless the user explicitly changed it.
- [ ] Quick Buy inherits the known category.
- [ ] Meal-plan ingredients inherit the known category.
- [ ] `GET /api/items/suggest` returns the curated category and treats `Cola`/`cola` as one.
- [ ] Existing corrupted rows are repaired by a reversible data migration.
- [ ] Full PHP suite green; Pint + PHPStan clean; `npm run build` clean.
- [ ] The §0.6 reproduction steps, repeated after the fix, show the bug is gone.
