<template>
  <div class="min-h-screen bg-gray-50 dark:bg-[var(--bg-primary)]">
    <!-- Header -->
    <header class="bg-white dark:bg-[var(--bg-secondary)] shadow-sm sticky top-0 z-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <Link href="/" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
              ←
            </Link>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
              📅 Essensplaner
            </h1>
          </div>

          <div class="flex items-center gap-2 sm:gap-4">
            <!-- Week navigation -->
            <div class="flex items-center gap-2">
              <button
                @click="previousWeek"
                class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
              >
                ←
              </button>
              <span class="text-sm text-gray-600 dark:text-gray-300">
                {{ weekRangeText }}
              </span>
              <button
                @click="nextWeek"
                class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
              >
                →
              </button>
            </div>

            <!-- Theme toggle -->
            <button
              @click="toggleTheme"
              class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
            >
              <span v-if="isDark">☀️</span>
              <span v-else>🌙</span>
            </button>
          </div>
        </div>
      </div>
    </header>

    <!-- Main content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <!-- Loading state -->
      <div v-if="mealPlansStore.loading" class="text-center py-8">
        <div class="text-gray-500 dark:text-gray-400">Lädt...</div>
      </div>

      <!-- Week view -->
      <div v-else class="space-y-6">
        <!-- Days of the week -->
        <div v-for="day in weekDays" :key="day.date" class="bg-white dark:bg-[var(--bg-secondary)] rounded-lg shadow-sm p-4">
          <!-- Day header -->
          <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-200 dark:border-gray-700">
            <div>
              <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                {{ day.dayName }}
              </h2>
              <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ day.formattedDate }}
              </p>
            </div>
          </div>

          <!-- Meal slots -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Breakfast -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
              <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Frühstück
              </h3>
              <MealCard
                :meal="getMeal(day.date, 'breakfast')"
                :date="day.date"
                mealType="breakfast"
                @add-meal="openAddMealModal"
                @click-meal="openMealDetails"
              />
            </div>

            <!-- Lunch -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
              <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Mittagessen
              </h3>
              <MealCard
                :meal="getMeal(day.date, 'lunch')"
                :date="day.date"
                mealType="lunch"
                @add-meal="openAddMealModal"
                @click-meal="openMealDetails"
              />
            </div>

            <!-- Dinner -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
              <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Abendessen
              </h3>
              <MealCard
                :meal="getMeal(day.date, 'dinner')"
                :date="day.date"
                mealType="dinner"
                @add-meal="openAddMealModal"
                @click-meal="openMealDetails"
              />
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Add meal modal -->
    <div v-if="showAddMealModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white dark:bg-[var(--bg-secondary)] rounded-lg max-w-md w-full p-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
          Mahlzeit hinzufügen
        </h2>

        <form @submit.prevent="addMeal">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Titel
              </label>
              <input
                v-model="newMealTitle"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[var(--bg-primary)] text-gray-900 dark:text-white"
                placeholder="z.B. Spaghetti Bolognese"
              />
            </div>

            <div class="flex gap-2">
              <button
                type="submit"
                class="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg"
              >
                Hinzufügen
              </button>
              <button
                type="button"
                @click="closeAddMealModal"
                class="flex-1 bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500 text-gray-900 dark:text-white px-4 py-2 rounded-lg"
              >
                Abbrechen
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <!-- Meal details modal -->
    <div v-if="showMealDetailsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white dark:bg-[var(--bg-secondary)] rounded-lg max-w-2xl w-full p-6 max-h-[80vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">
            {{ selectedMeal?.title }}
          </h2>
          <button
            @click="closeMealDetailsModal"
            class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
          >
            ✕
          </button>
        </div>

        <!-- Ingredients -->
        <div class="mb-6">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
              Zutaten
            </h3>
            <button
              v-if="selectedMeal?.ingredients && selectedMeal.ingredients.length > 0"
              @click="addAllToShoppingList"
              class="text-sm bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded-lg"
            >
              Alle zur Einkaufsliste
            </button>
          </div>

          <!-- Ingredients list -->
          <div v-if="selectedMeal?.ingredients && selectedMeal.ingredients.length > 0" class="space-y-2 mb-4">
            <div
              v-for="ingredient in selectedMeal.ingredients"
              :key="ingredient.id"
              class="flex items-center justify-between p-2 bg-gray-50 dark:bg-[var(--bg-primary)] rounded"
            >
              <span class="text-gray-900 dark:text-white">
                {{ ingredient.name }}
                <span v-if="ingredient.quantity" class="text-gray-500 dark:text-gray-400 text-sm">
                  ({{ ingredient.quantity }})
                </span>
              </span>
              <button
                @click="removeIngredient(ingredient.id)"
                class="text-red-500 hover:text-red-600 text-sm"
              >
                🗑️
              </button>
            </div>
          </div>
          <div v-else class="text-gray-500 dark:text-gray-400 text-sm mb-4">
            Keine Zutaten hinzugefügt
          </div>

          <!-- Add ingredient form -->
          <form @submit.prevent="addIngredient" class="space-y-2">
            <div class="grid grid-cols-3 gap-2">
              <input
                v-model="newIngredientName"
                type="text"
                required
                class="col-span-2 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[var(--bg-primary)] text-gray-900 dark:text-white text-sm"
                placeholder="Zutat"
                @input="handleIngredientSearch"
              />
              <input
                v-model="newIngredientQuantity"
                type="text"
                class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-[var(--bg-primary)] text-gray-900 dark:text-white text-sm"
                placeholder="Menge"
              />
            </div>

            <!-- Autocomplete suggestions -->
            <div v-if="ingredientSuggestions.length > 0" class="bg-white dark:bg-[var(--bg-primary)] border border-gray-300 dark:border-gray-600 rounded-lg max-h-40 overflow-y-auto">
              <button
                v-for="suggestion in ingredientSuggestions"
                :key="suggestion.id"
                type="button"
                @click="selectIngredientSuggestion(suggestion)"
                class="w-full text-left px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white text-sm"
              >
                {{ suggestion.name }}
              </button>
            </div>

            <button
              type="submit"
              class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm"
            >
              Zutat hinzufügen
            </button>
          </form>
        </div>

        <!-- Delete meal button -->
        <button
          @click="deleteMeal"
          class="w-full bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg"
        >
          Mahlzeit löschen
        </button>
      </div>
    </div>

    <!-- Toast notifications -->
    <div class="fixed bottom-4 right-4 space-y-2 z-50">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="px-4 py-3 rounded-lg shadow-lg text-white max-w-sm"
        :class="{
          'bg-green-500': toast.type === 'success',
          'bg-red-500': toast.type === 'error',
          'bg-blue-500': toast.type === 'info',
        }"
      >
        {{ toast.message }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useMealPlansStore } from '../Stores/mealPlans';
import { useItemsStore } from '../Stores/items';
import { useTheme } from '../Composables/useTheme';
import { useToast } from '../Composables/useToast';
import MealCard from '../Components/MealCard.vue';

const mealPlansStore = useMealPlansStore();
const itemsStore = useItemsStore();
const { isDark, toggleTheme } = useTheme();
const { toasts, success, error: showError } = useToast();

// Week navigation
const currentWeekStart = ref(getMonday(new Date()));

const weekDays = computed(() => {
  const days = [];
  const start = new Date(currentWeekStart.value);

  for (let i = 0; i < 7; i++) {
    const date = new Date(start);
    date.setDate(start.getDate() + i);

    days.push({
      date: formatDateForAPI(date),
      dayName: getDayName(date.getDay()),
      formattedDate: formatDateForDisplay(date),
    });
  }

  return days;
});

const weekRangeText = computed(() => {
  const start = new Date(currentWeekStart.value);
  const end = new Date(start);
  end.setDate(start.getDate() + 6);

  return `${formatDateForDisplay(start)} - ${formatDateForDisplay(end)}`;
});

// Add meal modal
const showAddMealModal = ref(false);
const newMealTitle = ref('');
const addMealDate = ref('');
const addMealType = ref('');

// Meal details modal
const showMealDetailsModal = ref(false);
const selectedMeal = ref(null);

// Ingredient form
const newIngredientName = ref('');
const newIngredientQuantity = ref('');
const ingredientSuggestions = ref([]);
let searchTimeout = null;

// Helper functions
function getMonday(date) {
  const d = new Date(date);
  const day = d.getDay();
  const diff = d.getDate() - day + (day === 0 ? -6 : 1);
  d.setDate(diff);
  d.setHours(0, 0, 0, 0);
  return d;
}

function formatDateForAPI(date) {
  return date.toISOString().split('T')[0];
}

function formatDateForDisplay(date) {
  return date.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit' });
}

function getDayName(dayIndex) {
  const days = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
  return days[dayIndex];
}

function getMeal(date, mealType) {
  return mealPlansStore.getMealPlan(date, mealType);
}

// Week navigation
function previousWeek() {
  const newStart = new Date(currentWeekStart.value);
  newStart.setDate(newStart.getDate() - 7);
  currentWeekStart.value = newStart;
  loadMealPlans();
}

function nextWeek() {
  const newStart = new Date(currentWeekStart.value);
  newStart.setDate(newStart.getDate() + 7);
  currentWeekStart.value = newStart;
  loadMealPlans();
}

// Add meal
function openAddMealModal(date, mealType) {
  addMealDate.value = date;
  addMealType.value = mealType;
  newMealTitle.value = '';
  showAddMealModal.value = true;
}

function closeAddMealModal() {
  showAddMealModal.value = false;
  newMealTitle.value = '';
}

async function addMeal() {
  try {
    await mealPlansStore.createMealPlan({
      date: addMealDate.value,
      meal_type: addMealType.value,
      title: newMealTitle.value,
    });

    success('Mahlzeit hinzugefügt');
    closeAddMealModal();
  } catch (err) {
    showError('Fehler beim Hinzufügen der Mahlzeit');
  }
}

// Meal details
function openMealDetails(meal) {
  selectedMeal.value = meal;
  showMealDetailsModal.value = true;
}

function closeMealDetailsModal() {
  showMealDetailsModal.value = false;
  selectedMeal.value = null;
  newIngredientName.value = '';
  newIngredientQuantity.value = '';
  ingredientSuggestions.value = [];
}

async function deleteMeal() {
  if (!confirm('Möchten Sie diese Mahlzeit wirklich löschen?')) return;

  try {
    await mealPlansStore.deleteMealPlan(selectedMeal.value.id);
    success('Mahlzeit gelöscht');
    closeMealDetailsModal();
  } catch (err) {
    showError('Fehler beim Löschen der Mahlzeit');
  }
}

// Ingredients
async function handleIngredientSearch() {
  if (newIngredientName.value.length < 2) {
    ingredientSuggestions.value = [];
    return;
  }

  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(async () => {
    try {
      ingredientSuggestions.value = await itemsStore.searchInventory(newIngredientName.value);
    } catch (err) {
      console.error('Error searching ingredients:', err);
    }
  }, 300);
}

function selectIngredientSuggestion(suggestion) {
  newIngredientName.value = suggestion.name;
  ingredientSuggestions.value = [];
}

async function addIngredient() {
  try {
    await mealPlansStore.addIngredient(selectedMeal.value.id, {
      name: newIngredientName.value,
      quantity: newIngredientQuantity.value || null,
    });

    // Refresh the selected meal
    selectedMeal.value = mealPlansStore.mealPlans.find(m => m.id === selectedMeal.value.id);

    newIngredientName.value = '';
    newIngredientQuantity.value = '';
    success('Zutat hinzugefügt');
  } catch (err) {
    showError('Fehler beim Hinzufügen der Zutat');
  }
}

async function removeIngredient(ingredientId) {
  try {
    await mealPlansStore.removeIngredient(selectedMeal.value.id, ingredientId);

    // Refresh the selected meal
    selectedMeal.value = mealPlansStore.mealPlans.find(m => m.id === selectedMeal.value.id);

    success('Zutat entfernt');
  } catch (err) {
    showError('Fehler beim Entfernen der Zutat');
  }
}

async function addAllToShoppingList() {
  try {
    const result = await mealPlansStore.addIngredientsToShoppingList(selectedMeal.value.id);
    success(result.message);

    // Refresh items to show new shopping list items
    await itemsStore.fetchItems();
  } catch (err) {
    showError('Fehler beim Hinzufügen zur Einkaufsliste');
  }
}

// Load meal plans
async function loadMealPlans() {
  await mealPlansStore.fetchMealPlans(formatDateForAPI(currentWeekStart.value));
}

onMounted(() => {
  loadMealPlans();
});
</script>
