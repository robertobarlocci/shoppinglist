<template>
  <div class="bg-gray-50 dark:bg-[var(--bg-primary)] rounded-lg p-4">
    <!-- User name header (for parents viewing kids' lunchboxes) -->
    <div v-if="showUserName && userName" class="mb-3 pb-2 border-b border-gray-200 dark:border-gray-700">
      <h3 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
        <span>🍱</span>
        <span>{{ userName }}</span>
      </h3>
    </div>

    <!-- List of current lunchbox items -->
    <div v-if="items && items.length > 0" class="space-y-2 mb-3">
      <div
        v-for="item in items"
        :key="item.id"
        class="flex items-center justify-between bg-white dark:bg-gray-700 rounded-md px-3 py-2"
      >
        <span class="text-gray-900 dark:text-white text-sm">{{ item.item_name }}</span>
        <button
          v-if="canEdit"
          @click="deleteItem(item.id)"
          class="text-red-500 hover:text-red-700 dark:hover:text-red-400 text-sm transition-colors"
          :disabled="deleting"
        >
          ✕
        </button>
      </div>
    </div>

    <!-- Empty state -->
    <div
      v-else-if="!canEdit"
      class="text-center py-4 text-gray-400 dark:text-gray-500 text-sm"
    >
      Keine Einträge
    </div>

    <!-- Add new item (only for kids) -->
    <div v-if="canEdit" class="relative">
      <input
        v-model="newItemName"
        @input="onInput"
        @keydown.enter="addItem"
        @keydown.down.prevent="navigateDown"
        @keydown.up.prevent="navigateUp"
        @keydown.esc="clearSuggestions"
        @blur="onBlur"
        type="text"
        placeholder="Neues Item hinzufügen..."
        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 text-sm"
        :disabled="adding"
      />

      <!-- Autocomplete suggestions dropdown -->
      <div
        v-if="showSuggestions && suggestions.length > 0"
        class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg max-h-48 overflow-y-auto"
      >
        <div
          v-for="(suggestion, index) in suggestions"
          :key="index"
          @mousedown.prevent="selectSuggestion(suggestion)"
          :class="[
            'px-3 py-2 cursor-pointer text-sm',
            index === selectedSuggestionIndex
              ? 'bg-blue-100 dark:bg-blue-900 text-gray-900 dark:text-white'
              : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600'
          ]"
        >
          {{ suggestion }}
        </div>
      </div>

      <!-- Add button (optional, Enter key also works) -->
      <div v-if="newItemName.trim()" class="mt-2">
        <button
          @click="addItem"
          :disabled="adding"
          class="w-full px-3 py-2 bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700 text-white rounded-md text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ adding ? 'Wird hinzugefügt...' : 'Hinzufügen' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useLunchboxStore } from '../Stores/lunchbox';
import { useToast } from '../Composables/useToast';

const props = defineProps({
  date: {
    type: String,
    required: true,
  },
  userId: {
    type: Number,
    required: true,
  },
  userName: {
    type: String,
    default: null,
  },
  items: {
    type: Array,
    default: () => [],
  },
  canEdit: {
    type: Boolean,
    default: false,
  },
  showUserName: {
    type: Boolean,
    default: false,
  },
});

const lunchboxStore = useLunchboxStore();
const { success, error: showError } = useToast();

const newItemName = ref('');
const adding = ref(false);
const deleting = ref(false);
const suggestions = ref([]);
const showSuggestions = ref(false);
const selectedSuggestionIndex = ref(-1);
const debounceTimer = ref(null);
const showDeleteConfirm = ref(false);
const itemToDelete = ref(null);

// Fetch autocomplete suggestions
const fetchSuggestions = async () => {
  if (!newItemName.value.trim()) {
    suggestions.value = [];
    showSuggestions.value = false;
    return;
  }

  try {
    const results = await lunchboxStore.getAutocompleteSuggestions(newItemName.value.trim());
    suggestions.value = results;
    showSuggestions.value = results.length > 0;
    selectedSuggestionIndex.value = -1;
  } catch (error) {
    console.error('Error fetching suggestions:', error);
  }
};

// Handle input with debounce
const onInput = () => {
  if (debounceTimer.value) {
    clearTimeout(debounceTimer.value);
  }

  debounceTimer.value = setTimeout(() => {
    fetchSuggestions();
  }, 300);
};

// Navigate suggestions with arrow keys
const navigateDown = () => {
  if (suggestions.value.length > 0) {
    selectedSuggestionIndex.value = Math.min(
      selectedSuggestionIndex.value + 1,
      suggestions.value.length - 1
    );
  }
};

const navigateUp = () => {
  if (suggestions.value.length > 0) {
    selectedSuggestionIndex.value = Math.max(selectedSuggestionIndex.value - 1, 0);
  }
};

// Select a suggestion
const selectSuggestion = (suggestion) => {
  newItemName.value = suggestion;
  clearSuggestions();
  addItem();
};

// Clear suggestions
const clearSuggestions = () => {
  suggestions.value = [];
  showSuggestions.value = false;
  selectedSuggestionIndex.value = -1;
};

// Handle blur (close suggestions after a short delay)
const onBlur = () => {
  setTimeout(() => {
    clearSuggestions();
  }, 200);
};

// Add new item
const addItem = async () => {
  // If a suggestion is selected, use it
  if (selectedSuggestionIndex.value >= 0) {
    newItemName.value = suggestions.value[selectedSuggestionIndex.value];
    clearSuggestions();
  }

  const itemName = newItemName.value.trim();
  if (!itemName) return;

  adding.value = true;
  try {
    await lunchboxStore.createLunchboxItem({
      date: props.date,
      item_name: itemName,
    });
    newItemName.value = '';
    clearSuggestions();
    success('Item hinzugefügt');
  } catch (error) {
    console.error('Error adding lunchbox item:', error);
    showError('Fehler beim Hinzufügen des Items');
  } finally {
    adding.value = false;
  }
};

// Delete item
const deleteItem = async (itemId) => {
  if (!confirm('Möchtest du dieses Item wirklich löschen?')) return;

  deleting.value = true;
  try {
    await lunchboxStore.deleteLunchboxItem(itemId);
    success('Item gelöscht');
  } catch (error) {
    console.error('Error deleting lunchbox item:', error);
    showError('Fehler beim Löschen des Items');
  } finally {
    deleting.value = false;
  }
};
</script>
