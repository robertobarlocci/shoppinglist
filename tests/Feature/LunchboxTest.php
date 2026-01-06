<?php

namespace Tests\Feature;

use App\Models\LunchboxItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LunchboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_kids_can_create_lunchbox_items(): void
    {
        $kid = User::factory()->create(['role' => 'kid']);

        $response = $this->actingAs($kid)->postJson('/api/lunchbox', [
            'date' => '2026-01-10',
            'item_name' => 'Gurke',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('lunchbox_items', [
            'user_id' => $kid->id,
            'item_name' => 'Gurke',
            'date' => '2026-01-10',
        ]);
    }

    public function test_kids_can_delete_their_own_lunchbox_items(): void
    {
        $kid = User::factory()->create(['role' => 'kid']);
        $item = LunchboxItem::create([
            'user_id' => $kid->id,
            'date' => '2026-01-10',
            'item_name' => 'Apfel',
        ]);

        $response = $this->actingAs($kid)->deleteJson("/api/lunchbox/{$item->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('lunchbox_items', [
            'id' => $item->id,
        ]);
    }

    public function test_kids_cannot_delete_other_kids_lunchbox_items(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);
        $kid1 = User::factory()->create(['role' => 'kid', 'parent_id' => $parent->id]);
        $kid2 = User::factory()->create(['role' => 'kid', 'parent_id' => $parent->id]);

        $item = LunchboxItem::create([
            'user_id' => $kid1->id,
            'date' => '2026-01-10',
            'item_name' => 'Banane',
        ]);

        $response = $this->actingAs($kid2)->deleteJson("/api/lunchbox/{$item->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('lunchbox_items', [
            'id' => $item->id,
        ]);
    }

    public function test_parents_cannot_create_lunchbox_items(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);

        $response = $this->actingAs($parent)->postJson('/api/lunchbox', [
            'date' => '2026-01-10',
            'item_name' => 'Healthy Snack',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('lunchbox_items', [
            'item_name' => 'Healthy Snack',
        ]);
    }

    public function test_parents_cannot_delete_lunchbox_items(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);
        $kid = User::factory()->create(['role' => 'kid', 'parent_id' => $parent->id]);

        $item = LunchboxItem::create([
            'user_id' => $kid->id,
            'date' => '2026-01-10',
            'item_name' => 'Candy',
        ]);

        $response = $this->actingAs($parent)->deleteJson("/api/lunchbox/{$item->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('lunchbox_items', [
            'id' => $item->id,
        ]);
    }

    public function test_parents_can_view_their_children_lunchbox_items(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);
        $kid = User::factory()->create(['role' => 'kid', 'parent_id' => $parent->id]);

        LunchboxItem::create([
            'user_id' => $kid->id,
            'date' => '2026-01-10',
            'item_name' => 'Apfel',
        ]);

        $response = $this->actingAs($parent)->getJson('/api/lunchbox?start_date=2026-01-06');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'item_name' => 'Apfel',
        ]);
    }

    public function test_parents_cannot_view_other_parents_children_lunchbox_items(): void
    {
        $parent1 = User::factory()->create(['role' => 'parent']);
        $parent2 = User::factory()->create(['role' => 'parent']);
        $kid1 = User::factory()->create(['role' => 'kid', 'parent_id' => $parent1->id]);

        LunchboxItem::create([
            'user_id' => $kid1->id,
            'date' => '2026-01-10',
            'item_name' => 'Secret Snack',
        ]);

        $response = $this->actingAs($parent2)->getJson('/api/lunchbox?start_date=2026-01-06');

        $response->assertStatus(200);
        $response->assertJsonMissing([
            'item_name' => 'Secret Snack',
        ]);
    }

    public function test_kids_can_only_view_their_own_lunchbox_items(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);
        $kid1 = User::factory()->create(['role' => 'kid', 'parent_id' => $parent->id]);
        $kid2 = User::factory()->create(['role' => 'kid', 'parent_id' => $parent->id]);

        LunchboxItem::create([
            'user_id' => $kid1->id,
            'date' => '2026-01-10',
            'item_name' => 'Kid1 Apple',
        ]);

        LunchboxItem::create([
            'user_id' => $kid2->id,
            'date' => '2026-01-10',
            'item_name' => 'Kid2 Banana',
        ]);

        $response = $this->actingAs($kid1)->getJson('/api/lunchbox?start_date=2026-01-06');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'item_name' => 'Kid1 Apple',
        ]);
        $response->assertJsonMissing([
            'item_name' => 'Kid2 Banana',
        ]);
    }

    public function test_autocomplete_returns_suggestions_from_siblings(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);
        $kid1 = User::factory()->create(['role' => 'kid', 'parent_id' => $parent->id]);
        $kid2 = User::factory()->create(['role' => 'kid', 'parent_id' => $parent->id]);

        LunchboxItem::create([
            'user_id' => $kid2->id,
            'date' => '2026-01-10',
            'item_name' => 'Gurke',
        ]);

        $response = $this->actingAs($kid1)->getJson('/api/lunchbox/autocomplete?query=Gur');

        $response->assertStatus(200);
        $response->assertJson(['Gurke']);
    }

    public function test_autocomplete_does_not_return_items_from_other_families(): void
    {
        $parent1 = User::factory()->create(['role' => 'parent']);
        $parent2 = User::factory()->create(['role' => 'parent']);
        $kid1 = User::factory()->create(['role' => 'kid', 'parent_id' => $parent1->id]);
        $kid2 = User::factory()->create(['role' => 'kid', 'parent_id' => $parent2->id]);

        LunchboxItem::create([
            'user_id' => $kid2->id,
            'date' => '2026-01-10',
            'item_name' => 'Secret Item',
        ]);

        $response = $this->actingAs($kid1)->getJson('/api/lunchbox/autocomplete?query=Secret');

        $response->assertStatus(200);
        $response->assertJsonMissing(['Secret Item']);
    }

    public function test_lunchbox_items_require_authentication(): void
    {
        $response = $this->getJson('/api/lunchbox');

        $response->assertStatus(401);
    }

    public function test_lunchbox_item_creation_requires_valid_data(): void
    {
        $kid = User::factory()->create(['role' => 'kid']);

        $response = $this->actingAs($kid)->postJson('/api/lunchbox', [
            'date' => 'invalid-date',
            'item_name' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['date', 'item_name']);
    }
}
