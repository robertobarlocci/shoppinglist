<template>
  <div v-if="suggestions && suggestions.length > 0" class="mt-2 space-y-1">
    <div
      v-for="suggestion in suggestions"
      :key="suggestion.id"
      class="flex items-center justify-between p-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded text-sm"
    >
      <div class="flex items-center gap-2 flex-1">
        <div
          class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0"
          :style="{ backgroundColor: suggestion.user?.avatar_color || '#gray' }"
        >
          {{ suggestion.user?.name?.charAt(0) || '?' }}
        </div>
        <div class="flex-1 min-w-0">
          <p class="font-medium text-gray-900 dark:text-white truncate">
            {{ suggestion.title }}
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400">
            von {{ suggestion.user?.name || 'Unbekannt' }}
          </p>
        </div>
      </div>

      <!-- Action buttons for parents -->
      <div v-if="isParent && suggestion.status === 'pending'" class="flex items-center gap-1 flex-shrink-0">
        <button
          @click.stop="$emit('approve', suggestion.id)"
          class="p-1 text-green-600 hover:bg-green-100 dark:hover:bg-green-900 rounded"
          title="Genehmigen"
        >
          ✓
        </button>
        <button
          @click.stop="$emit('reject', suggestion.id)"
          class="p-1 text-red-600 hover:bg-red-100 dark:hover:bg-red-900 rounded"
          title="Ablehnen"
        >
          ✕
        </button>
      </div>

      <!-- Status badge -->
      <div v-else class="flex-shrink-0">
        <span
          v-if="suggestion.status === 'approved'"
          class="text-xs px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded"
        >
          ✓ Genehmigt
        </span>
        <span
          v-else-if="suggestion.status === 'rejected'"
          class="text-xs px-2 py-1 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 rounded"
        >
          ✕ Abgelehnt
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  suggestions: {
    type: Array,
    default: () => [],
  },
  isParent: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['approve', 'reject']);
</script>
