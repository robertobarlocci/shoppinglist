<template>
  <div class="min-h-screen bg-gray-50 dark:bg-[var(--bg-primary)]">
    <!-- Header -->
    <header class="bg-white dark:bg-[var(--bg-secondary)] shadow-sm sticky top-0 z-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            🛒 Chnubber-Shop
          </h1>

          <div class="flex items-center gap-4">
            <!-- Offline indicator -->
            <div v-if="!isOnline" class="text-yellow-500 text-sm">
              ⚠️ Offline
            </div>

            <!-- Sync button -->
            <button
              v-if="pendingCount > 0"
              @click="syncNow"
              class="text-sm text-blue-500 hover:text-blue-600"
            >
              🔄 {{ pendingCount }} ausstehend
            </button>

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
              <a href="/profile" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                Einstellungen
              </a>
              <span class="text-gray-300 dark:text-gray-600">|</span>
              <form @submit.prevent="logout" class="inline">
                <button type="submit" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
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
        <section class="card p-6 bg-quick-buy text-white">
          <h2 class="text-xl font-bold mb-4">🔥 Quick Buy</h2>
          <p class="text-sm opacity-90 mb-4">Schnelle Kiosk-Einkäufe</p>

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
          <input
            v-model="searchQuery"
            @input="handleSearch"
            type="text"
            placeholder="Artikel suchen oder neu hinzufügen..."
            class="input"
          />

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
                  <span v-if="item.list_type === 'inventory'" class="text-xs px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded">Vorrat</span>
                  <span v-else-if="item.list_type === 'to_buy'" class="text-xs px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded">Einkaufsliste</span>
                  <span v-else-if="item.list_type === 'quick_buy'" class="text-xs px-2 py-1 bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 rounded">Quick Buy</span>
                </div>
                <span v-if="item.quantity" class="text-sm text-gray-500 ml-2">{{ item.quantity }}</span>
              </div>
              <button class="text-blue-500 hover:text-blue-600">+</button>
            </div>
          </div>

          <!-- Add new button -->
          <button
            v-if="searchQuery.length > 0 && suggestions.length === 0"
            @click="addNewItem"
            class="mt-2 w-full p-3 bg-gray-50 dark:bg-gray-800 rounded-lg text-left dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700"
          >
            ➕ "{{ searchQuery }}" als neuen Artikel hinzufügen
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

            <div v-else class="space-y-3">
              <div
                v-for="item in toBuyItems"
                :key="item.id"
                class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg flex items-center justify-between"
              >
                <div>
                  <span class="dark:text-white">{{ item.name }}</span>
                  <span v-if="item.quantity" class="text-sm text-gray-500 ml-2">{{ item.quantity }}</span>
                  <span v-if="item.is_recurring" class="ml-2">🔄</span>
                </div>
                <div class="flex gap-2">
                  <button
                    @click="checkItem(item)"
                    class="text-green-500 hover:text-green-600"
                    title="Abhaken"
                  >
                    ✓
                  </button>
                  <button
                    @click="deleteItem(item)"
                    class="text-red-500 hover:text-red-600"
                    title="Löschen"
                  >
                    🗑️
                  </button>
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

            <div v-else class="space-y-3 max-h-96 overflow-y-auto">
              <div
                v-for="item in inventoryItems"
                :key="item.id"
                class="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg flex items-center justify-between"
              >
                <div>
                  <span class="dark:text-white">{{ item.name }}</span>
                  <span v-if="item.recurring_schedule" class="ml-2 text-sm text-gray-500">
                    🔄 {{ item.recurring_schedule.description }}
                  </span>
                </div>
                <button
                  @click="moveToList(item)"
                  class="text-blue-500 hover:text-blue-600"
                  title="Zur Liste"
                >
                  →
                </button>
              </div>
            </div>
          </section>
        </div>
      </div>
    </main>

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
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { useItemsStore } from '../Stores/items';
import { useToast } from '../Composables/useToast';
import { useTheme } from '../Composables/useTheme';
import { useOfflineSync } from '../Composables/useOfflineSync';

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

const quickBuyItems = computed(() => itemsStore.quickBuyItems);
const toBuyItems = computed(() => itemsStore.toBuyItems);
const inventoryItems = computed(() => itemsStore.inventoryItems);

onMounted(() => {
  itemsStore.fetchItems();
});

let searchTimeout = null;
const handleSearch = async () => {
  if (searchQuery.value.length < 2) {
    suggestions.value = [];
    return;
  }

  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(async () => {
    suggestions.value = await itemsStore.searchInventory(searchQuery.value);
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

    // Find 'other' category or use first available category as fallback
    const categoryId = props.categories.find(c => c.slug === 'other')?.id || props.categories[0]?.id;

    await itemsStore.createItem({
      name: quickBuyInput.value,
      list_type: 'quick_buy',
      category_id: categoryId,
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
      return;
    }

    // Find 'other' category or use first available category as fallback
    const categoryId = props.categories.find(c => c.slug === 'other')?.id || props.categories[0]?.id;

    await itemsStore.createItem({
      name: searchQuery.value,
      list_type: 'to_buy',
      category_id: categoryId,
    });
    success(`"${searchQuery.value}" hinzugefügt`);
    searchQuery.value = '';
    suggestions.value = [];
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
  try {
    await itemsStore.moveItem(item.id, 'inventory');
    success(`"${item.name}" abgehakt`);
  } catch (err) {
    error('Fehler beim Abhaken');
  }
};

const deleteItem = async (item) => {
  if (!confirm(`"${item.name}" wirklich löschen?`)) return;

  try {
    await itemsStore.deleteItem(item.id);
    success(`"${item.name}" gelöscht`);
  } catch (err) {
    error('Fehler beim Löschen');
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
  router.post('/logout');
};
</script>
