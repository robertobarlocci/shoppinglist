<template>
  <div class="min-h-screen bg-gray-50 dark:bg-[var(--bg-primary)]">
    <!-- Header -->
    <header class="bg-white dark:bg-[var(--bg-secondary)] shadow-sm sticky top-0 z-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
            🛒 Shop
          </h1>

          <div class="flex items-center gap-2 sm:gap-4">
            <!-- Quick Buy Toggle -->
            <button
              @click="toggleQuickBuy"
              class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 relative"
              :class="{ 'bg-orange-100 dark:bg-orange-900': showQuickBuy }"
              title="Quick Buy"
            >
              <span class="text-lg">🔥</span>
              <span v-if="quickBuyItems.length > 0" class="absolute -top-1 -right-1 bg-orange-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                {{ quickBuyItems.length }}
              </span>
            </button>

            <!-- Meal Planner -->
            <Link
              href="/meal-planner"
              class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
              title="Essensplaner"
            >
              <span class="text-lg">📅</span>
            </Link>

            <!-- Lunchbox -->
            <Link
              href="/lunchbox"
              class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
              title="Lunchbox"
            >
              <span class="text-lg">🍱</span>
            </Link>

            <!-- Offline indicator -->
            <div v-if="!isOnline" class="text-yellow-500 text-sm hidden sm:block">
              ⚠️ Offline
            </div>

            <!-- Sync button -->
            <button
              v-if="pendingCount > 0"
              @click="syncNow"
              class="text-sm text-blue-500 hover:text-blue-600 hidden sm:block"
            >
              🔄 {{ pendingCount }} ausstehend
            </button>

            <!-- Activity History -->
            <ActivityHistory />

            <!-- Theme toggle -->
            <button
              @click="toggleTheme"
              class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
            >
              <span v-if="isDark">☀️</span>
              <span v-else>🌙</span>
            </button>

            <!-- User menu -->
            <div class="flex items-center gap-2">
              <div
                class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold"
                :style="{ backgroundColor: $page.props.auth.user.avatar_color }"
              >
                {{ $page.props.auth.user.name.charAt(0) }}
              </div>
              <a href="/profile" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hidden sm:inline">
                Einstellungen
              </a>
              <span class="text-gray-300 dark:text-gray-600 hidden sm:inline">|</span>
              <form @submit.prevent="logout" class="inline">
                <button type="submit" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white hidden sm:inline">
                  Abmelden
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="space-y-6">
        <!-- Quick Buy Section -->
        <section v-if="showQuickBuy" class="card p-6 bg-quick-buy text-white">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-xl font-bold">🔥 Quick Buy</h2>
              <p class="text-sm opacity-90">Schnelle Kiosk-Einkäufe</p>
            </div>
            <button
              @click="toggleQuickBuy"
              class="text-white/80 hover:text-white p-2"
              title="Schließen"
            >
              ✕
            </button>
          </div>

          <div class="mt-4">
            <input
              v-model="quickBuyInput"
              @keyup.enter="addQuickBuy"
              type="text"
              placeholder="Artikel hinzufügen..."
              class="input"
            />
          </div>

          <div v-if="quickBuyItems.length > 0" class="mt-4 space-y-2">
            <div
              v-for="item in quickBuyItems"
              :key="item.id"
              class="bg-white/20 rounded-lg p-3 flex items-center justify-between"
            >
              <span>{{ item.name }}</span>
              <button @click="checkItem(item)" class="text-white/80 hover:text-white">
                ✓
              </button>
            </div>
          </div>
        </section>

        <!-- Smart Input -->
        <section class="card p-6">
          <h3 class="text-lg font-semibold mb-4 dark:text-white">Was brauchst du?</h3>
          <div class="space-y-3">
            <input
              v-model="searchQuery"
              @input="handleSearch"
              type="text"
              placeholder="Artikel suchen oder neu hinzufügen..."
              class="input"
            />

            <!-- Category selector for new items -->
            <div v-if="showNewItemForm" class="flex gap-2">
              <select
                v-model="selectedCategoryForNewItem"
                @change="categoryTouchedByUser = true"
                class="input flex-1"
              >
                <option v-for="category in categories" :key="category.id" :value="category.id">
                  {{ category.name }}
                </option>
              </select>
            </div>
          </div>

          <!-- Search suggestions -->
          <div v-if="suggestions.length > 0" class="mt-2 space-y-2">
            <div
              v-for="item in suggestions"
              :key="item.id"
              class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg flex items-center justify-between cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700"
              @click="handleSuggestionClick(item)"
            >
              <div class="flex-1">
                <div class="flex items-center gap-2">
                  <span class="dark:text-white">⚡ {{ item.name }}</span>
                  <span v-if="item.category" class="text-xs px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded">{{ item.category.name }}</span>
                </div>
                <span v-if="item.quantity" class="text-sm text-gray-500 ml-2">{{ item.quantity }}</span>
              </div>
              <button class="text-blue-500 hover:text-blue-600">+</button>
            </div>
          </div>

          <!-- Add new button. Disabled while suggestions for the current input are still in
               flight, so a fast click can no longer create a duplicate under the default
               category before the real one is known (issue #2). -->
          <button
            v-if="showNewItemForm"
            @click="addNewItem"
            :disabled="isSearching"
            class="mt-2 w-full p-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 font-semibold disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="isSearching">Suche läuft …</span>
            <span v-else>➕ "{{ searchQuery }}" als neuen Artikel hinzufügen</span>
          </button>
        </section>

        <!-- Lists Grid -->
        <div class="grid md:grid-cols-2 gap-6">
          <!-- Shopping List -->
          <section class="card p-6">
            <h3 class="text-lg font-semibold mb-4 dark:text-white">
              🛒 Einkaufsliste ({{ toBuyItems.length }})
            </h3>

            <div v-if="toBuyItems.length === 0" class="text-gray-500 dark:text-gray-400 text-center py-8">
              Keine Artikel auf der Liste
            </div>

            <div v-else class="space-y-4">
              <!-- Group by category -->
              <div v-for="(items, categoryName) in groupedToBuyItems" :key="categoryName" class="space-y-2">
                <div class="flex items-center gap-2 mb-2">
                  <div
                    class="w-3 h-3 rounded-full"
                    :style="{ backgroundColor: getCategoryColor(categoryName) }"
                  ></div>
                  <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-300">
                    {{ categoryName }} ({{ items.length }})
                  </h4>
                </div>

                <div
                  v-for="item in items"
                  :key="item.id"
                  class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg flex items-center justify-between border-l-4"
                  :style="{ borderColor: getCategoryColor(categoryName) }"
                >
                  <div class="flex-1 cursor-pointer" @click="openEditModal(item)">
                    <span class="dark:text-white">{{ item.name }}</span>
                    <span v-if="item.quantity" class="text-sm text-gray-500 ml-2">{{ item.quantity }}</span>
                    <span v-if="item.is_recurring" class="ml-2">🔄</span>
                  </div>
                  <div class="flex gap-2">
                    <button
                      @click="openEditModal(item)"
                      class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                      title="Bearbeiten"
                      :disabled="isProcessing(item.id)"
                    >
                      ✏️
                    </button>
                    <button
                      @click="checkItem(item)"
                      class="text-green-500 hover:text-green-600 disabled:opacity-50 disabled:cursor-not-allowed"
                      title="Abhaken"
                      :disabled="isProcessing(item.id)"
                    >
                      <span v-if="isProcessing(item.id)" class="animate-spin">⏳</span>
                      <span v-else>✓</span>
                    </button>
                    <button
                      @click="deleteItem(item)"
                      class="text-red-500 hover:text-red-600 disabled:opacity-50 disabled:cursor-not-allowed"
                      title="Löschen"
                      :disabled="isProcessing(item.id)"
                    >
                      🗑️
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- Inventory -->
          <section class="card p-6">
            <h3 class="text-lg font-semibold mb-4 dark:text-white">
              📦 Inventar ({{ inventoryItems.length }})
            </h3>

            <div v-if="inventoryItems.length === 0" class="text-gray-500 dark:text-gray-400 text-center py-8">
              Noch keine Artikel im Inventar
            </div>

            <div v-else class="space-y-4 max-h-96 overflow-y-auto">
              <!-- Group by category -->
              <div v-for="(items, categoryName) in groupedInventoryItems" :key="categoryName" class="space-y-2">
                <div class="flex items-center gap-2 mb-2">
                  <div
                    class="w-3 h-3 rounded-full"
                    :style="{ backgroundColor: getCategoryColor(categoryName) }"
                  ></div>
                  <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-300">
                    {{ categoryName }} ({{ items.length }})
                  </h4>
                </div>

                <div
                  v-for="item in items"
                  :key="item.id"
                  class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg flex items-center justify-between border-l-4"
                  :style="{ borderColor: getCategoryColor(categoryName) }"
                >
                  <div class="flex-1 cursor-pointer" @click="openEditModal(item)">
                    <span class="dark:text-white">{{ item.name }}</span>
                    <span v-if="item.recurring_schedule" class="ml-2 text-sm text-gray-500">
                      🔄 {{ item.recurring_schedule.description }}
                    </span>
                  </div>
                  <div class="flex gap-2">
                    <button
                      @click="openEditModal(item)"
                      class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                      title="Bearbeiten"
                    >
                      ✏️
                    </button>
                    <button
                      @click="moveToList(item)"
                      class="text-blue-500 hover:text-blue-600"
                      title="Zur Liste"
                    >
                      →
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </section>
        </div>
      </div>
    </main>

    <!-- Edit Item Modal -->
    <div
      v-if="editingItem"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
      @click="closeEditModal"
    >
      <div
        class="bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 max-w-md w-full mx-4"
        @click.stop
      >
        <h3 class="text-xl font-semibold mb-4 dark:text-white">Artikel bearbeiten</h3>

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Name
            </label>
            <input
              v-model="editForm.name"
              type="text"
              class="input w-full"
              placeholder="Artikelname"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Menge (optional)
            </label>
            <input
              v-model="editForm.quantity"
              type="text"
              class="input w-full"
              placeholder="z.B. 500g, 2 Stück"
            />
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
              Kategorie
            </label>
            <select
              v-model="editForm.category_id"
              class="input w-full"
            >
              <option v-for="category in categories" :key="category.id" :value="category.id">
                {{ category.name }}
              </option>
            </select>
          </div>
        </div>

        <div class="flex gap-3 mt-6">
          <button
            @click="saveEdit"
            class="flex-1 bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 font-semibold"
          >
            Speichern
          </button>
          <button
            @click="closeEditModal"
            class="flex-1 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-white py-2 px-4 rounded-lg hover:bg-gray-400 dark:hover:bg-gray-700"
          >
            Abbrechen
          </button>
        </div>
      </div>
    </div>

    <!-- Toast Notifications -->
    <div class="fixed top-20 right-4 z-50 space-y-2">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="toast-enter bg-white dark:bg-gray-800 rounded-lg shadow-lg p-4 max-w-sm"
        :class="{
          'border-l-4 border-green-500': toast.type === 'success',
          'border-l-4 border-red-500': toast.type === 'error',
          'border-l-4 border-blue-500': toast.type === 'info',
        }"
      >
        <div class="flex items-start justify-between">
          <p class="dark:text-white">{{ toast.message }}</p>
          <button @click="removeToast(toast.id)" class="ml-4 text-gray-500 hover:text-gray-700">
            ×
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { useItemsStore } from '../Stores/items';
import { useToast } from '../Composables/useToast';
import { useTheme } from '../Composables/useTheme';
import { useOfflineSync } from '../Composables/useOfflineSync';
import ActivityHistory from '../Components/ActivityHistory.vue';

const props = defineProps({
  categories: Array,
});

const itemsStore = useItemsStore();
const { toasts, success, error, remove: removeToast } = useToast();
const { isDark, toggleTheme } = useTheme();
const { isOnline, pendingCount, syncPendingActions } = useOfflineSync();

const searchQuery = ref('');
const quickBuyInput = ref('');
const suggestions = ref([]);
const selectedCategoryForNewItem = ref(null);
// Issue #2: true only once the user has actively changed the dropdown for the current input.
// Until then the autocomplete is free to pre-fill it with the category the typed name already has.
const categoryTouchedByUser = ref(false);
// True from the moment the input changes until the debounced suggestion request has resolved.
const isSearching = ref(false);
const editingItem = ref(null);
const editForm = ref({
  name: '',
  quantity: '',
  category_id: null,
});
const showQuickBuy = ref(false);

// Track items being processed to prevent double-clicks
const processingItemIds = ref(new Set());
const isProcessing = (itemId) => processingItemIds.value.has(itemId);

const quickBuyItems = computed(() => itemsStore.quickBuyItems);
const toBuyItems = computed(() => itemsStore.toBuyItems);
const inventoryItems = computed(() => itemsStore.inventoryItems);

// Group items by category
const groupedToBuyItems = computed(() => {
  const grouped = {};
  toBuyItems.value.forEach(item => {
    const categoryName = item.category?.name || 'Sonstiges';
    if (!grouped[categoryName]) {
      grouped[categoryName] = [];
    }
    grouped[categoryName].push(item);
  });
  return grouped;
});

const groupedInventoryItems = computed(() => {
  const grouped = {};
  inventoryItems.value.forEach(item => {
    const categoryName = item.category?.name || 'Sonstiges';
    if (!grouped[categoryName]) {
      grouped[categoryName] = [];
    }
    grouped[categoryName].push(item);
  });
  return grouped;
});

// Check if the search query exactly matches any suggestion
const hasExactMatch = computed(() => {
  if (!searchQuery.value.trim() || suggestions.value.length === 0) {
    return false;
  }
  const query = searchQuery.value.trim().toLowerCase();
  return suggestions.value.some(item => item.name.toLowerCase() === query);
});

// Show add new button when there's input but no exact match in suggestions
const canAddNewItem = computed(() => {
  return searchQuery.value.length > 0 && !hasExactMatch.value;
});

// The form stays visible while a search is in flight so the layout does not jump; the button
// itself is disabled instead (issue #2 — clicking during the debounce created a duplicate
// carrying the default category).
const showNewItemForm = computed(() => {
  return canAddNewItem.value || (searchQuery.value.length > 0 && isSearching.value);
});

// The category the typed name already has, taken from the matching suggestion.
const knownCategoryIdForQuery = computed(() => {
  const query = searchQuery.value.trim().toLowerCase();
  if (!query) return null;

  const match = suggestions.value.find(item => item.name.toLowerCase() === query);
  return match?.category?.id ?? null;
});

const defaultCategoryId = () => {
  const defaultCategory = props.categories.find(c => c.slug === 'other') || props.categories[0];
  return defaultCategory?.id ?? null;
};

// Issue #2: the dropdown used to keep whatever the user last picked, so every following item
// silently inherited an unrelated category. It is reset after each successful add.
const resetNewItemCategory = () => {
  selectedCategoryForNewItem.value = defaultCategoryId();
  categoryTouchedByUser.value = false;
};

// Issue #2: the suggestion list already knows the category of the typed name — pre-select it,
// unless the user has deliberately chosen something else for this input.
watch(knownCategoryIdForQuery, (categoryId) => {
  if (categoryId && !categoryTouchedByUser.value) {
    selectedCategoryForNewItem.value = categoryId;
  }
});

onMounted(() => {
  itemsStore.fetchItems();
  resetNewItemCategory();
});

// Helper function to get category color
const getCategoryColor = (categoryName) => {
  const category = props.categories.find(c => c.name === categoryName);
  return category?.color || '#9E9E9E';
};

// Toggle Quick Buy visibility
const toggleQuickBuy = () => {
  showQuickBuy.value = !showQuickBuy.value;
};

// Edit modal functions
const openEditModal = (item) => {
  editingItem.value = item;
  editForm.value = {
    name: item.name,
    quantity: item.quantity || '',
    category_id: item.category?.id || selectedCategoryForNewItem.value,
  };
};

const closeEditModal = () => {
  editingItem.value = null;
  editForm.value = {
    name: '',
    quantity: '',
    category_id: null,
  };
};

const saveEdit = async () => {
  try {
    await itemsStore.updateItem(editingItem.value.id, {
      name: editForm.value.name,
      quantity: editForm.value.quantity || null,
      category_id: editForm.value.category_id,
    });
    success(`"${editForm.value.name}" aktualisiert`);
    closeEditModal();
  } catch (err) {
    error('Fehler beim Aktualisieren');
  }
};

let searchTimeout = null;
let searchRequestId = 0;
const handleSearch = async () => {
  // A new keystroke invalidates any category the user picked for the previous input.
  categoryTouchedByUser.value = false;
  clearTimeout(searchTimeout);

  if (searchQuery.value.length < 2) {
    suggestions.value = [];
    isSearching.value = false;
    return;
  }

  // Issue #2: mark the search as in flight IMMEDIATELY, not when the debounce fires, so the
  // "add new" button is unclickable for the whole window in which we do not yet know the
  // item's real category.
  isSearching.value = true;
  const requestId = ++searchRequestId;

  searchTimeout = setTimeout(async () => {
    try {
      const results = await itemsStore.searchInventory(searchQuery.value);
      // Ignore a response that a later keystroke has already superseded.
      if (requestId !== searchRequestId) return;
      suggestions.value = results;
    } catch (err) {
      if (requestId === searchRequestId) suggestions.value = [];
    } finally {
      if (requestId === searchRequestId) isSearching.value = false;
    }
  }, 300);
};

const addQuickBuy = async () => {
  if (!quickBuyInput.value.trim()) return;

  try {
    // Check for duplicates in quick_buy list
    const duplicate = quickBuyItems.value.find(
      item => item.name.toLowerCase() === quickBuyInput.value.trim().toLowerCase()
    );

    if (duplicate) {
      error(`"${quickBuyInput.value}" ist bereits in Quick Buy`);
      quickBuyInput.value = '';
      return;
    }

    // Issue #2: no category is sent. The server inherits the one this item name already has
    // and only falls back to "Sonstiges" for a genuinely new name — hardcoding 'other' here
    // was what made every Quick Buy item reset its category on check-off.
    await itemsStore.createItem({
      name: quickBuyInput.value.trim(),
      list_type: 'quick_buy',
    });
    success(`"${quickBuyInput.value}" zu Quick Buy hinzugefügt`);
    quickBuyInput.value = '';
  } catch (err) {
    error('Fehler beim Hinzufügen');
  }
};

const addNewItem = async () => {
  try {
    // Check for duplicates in to_buy list
    const duplicate = toBuyItems.value.find(
      item => item.name.toLowerCase() === searchQuery.value.trim().toLowerCase()
    );

    if (duplicate) {
      error(`"${searchQuery.value}" ist bereits auf der Einkaufsliste`);
      searchQuery.value = '';
      suggestions.value = [];
      isSearching.value = false;
      resetNewItemCategory();
      return;
    }

    const name = searchQuery.value.trim();

    // Issue #2: only send a category the user ACTIVELY chose. When the dropdown was never
    // touched it is merely showing a default, and sending it would tell the server "the user
    // picked Sonstiges" — the server instead inherits the category this item name already has.
    const payload = { name, list_type: 'to_buy' };
    if (categoryTouchedByUser.value) {
      payload.category_id = selectedCategoryForNewItem.value;
    }

    await itemsStore.createItem(payload);
    success(`"${name}" hinzugefügt`);
    searchQuery.value = '';
    suggestions.value = [];
    isSearching.value = false;
    resetNewItemCategory();
  } catch (err) {
    error('Fehler beim Hinzufügen');
  }
};

const handleSuggestionClick = async (item) => {
  try {
    if (item.list_type === 'to_buy') {
      error(`"${item.name}" ist bereits auf der Einkaufsliste`);
      return;
    }

    if (item.list_type === 'quick_buy') {
      error(`"${item.name}" ist bereits in Quick Buy`);
      return;
    }

    if (item.list_type === 'inventory') {
      await itemsStore.moveItem(item.id, 'to_buy');
      success(`"${item.name}" zur Einkaufsliste verschoben`);
      searchQuery.value = '';
      suggestions.value = [];
      isSearching.value = false;
      resetNewItemCategory();
    }
  } catch (err) {
    error('Fehler beim Verschieben');
  }
};

const moveFromInventory = async (item) => {
  try {
    await itemsStore.moveItem(item.id, 'to_buy');
    success(`"${item.name}" zur Einkaufsliste verschoben`);
    searchQuery.value = '';
    suggestions.value = [];
  } catch (err) {
    error('Fehler beim Verschieben');
  }
};

const moveToList = async (item) => {
  try {
    await itemsStore.moveItem(item.id, 'to_buy');
    success(`"${item.name}" zur Einkaufsliste verschoben`);
  } catch (err) {
    error('Fehler beim Verschieben');
  }
};

const checkItem = async (item) => {
  // Prevent double-clicks
  if (isProcessing(item.id)) {
    return;
  }

  processingItemIds.value.add(item.id);
  try {
    const result = await itemsStore.moveItem(item.id, 'inventory');
    if (result.deduplication) {
      success(result.message);
    } else {
      success(`"${item.name}" abgehakt`);
    }
  } catch (err) {
    error('Fehler beim Abhaken');
  } finally {
    processingItemIds.value.delete(item.id);
  }
};

const deleteItem = async (item) => {
  if (!confirm(`"${item.name}" wirklich löschen?`)) return;

  // Prevent double-clicks
  if (isProcessing(item.id)) {
    return;
  }

  processingItemIds.value.add(item.id);
  try {
    await itemsStore.deleteItem(item.id);
    success(`"${item.name}" gelöscht`);
  } catch (err) {
    error('Fehler beim Löschen');
  } finally {
    processingItemIds.value.delete(item.id);
  }
};

const syncNow = async () => {
  const result = await syncPendingActions();
  if (result.success) {
    success(result.message);
  } else {
    error(result.message);
  }
};

const logout = () => {
  router.post('/logout', {}, {
    onSuccess: () => {
      // Clear service worker caches to prevent stale content
      if (window.clearServiceWorkerCaches) {
        window.clearServiceWorkerCaches();
      }
      // Force full page reload to get fresh CSRF token
      // SPA navigation would keep the stale token in the meta tag
      window.location.href = '/login';
    },
  });
};
</script>
