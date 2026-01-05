<template>
  <div>
    <!-- Empty state - Add meal button -->
    <div
      v-if="!meal"
      @click="$emit('addMeal', date, mealType)"
      class="flex items-center justify-center min-h-[80px] border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-blue-500 dark:hover:border-blue-400 hover:bg-gray-50 dark:hover:bg-[var(--bg-primary)] transition-colors"
    >
      <span class="text-gray-400 dark:text-gray-500 text-sm">
        + Hinzufügen
      </span>
    </div>

    <!-- Meal card -->
    <div
      v-else
      @click="$emit('clickMeal', meal)"
      class="min-h-[80px] p-3 bg-gray-50 dark:bg-[var(--bg-primary)] rounded-lg cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
    >
      <div class="flex items-start justify-between">
        <div class="flex-1">
          <h4 class="font-medium text-gray-900 dark:text-white mb-1">
            {{ meal.title }}
          </h4>
          <p v-if="meal.ingredients && meal.ingredients.length > 0" class="text-xs text-gray-500 dark:text-gray-400">
            {{ meal.ingredients.length }} Zutat{{ meal.ingredients.length !== 1 ? 'en' : '' }}
          </p>
        </div>
        <button
          @click.stop="$emit('clickMeal', meal)"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
        >
          →
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  meal: {
    type: Object,
    default: null,
  },
  date: {
    type: String,
    required: true,
  },
  mealType: {
    type: String,
    required: true,
    validator: (value) => ['breakfast', 'lunch', 'dinner'].includes(value),
  },
});

defineEmits(['addMeal', 'clickMeal']);
</script>
