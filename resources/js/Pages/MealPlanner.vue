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

            <!-- Meals Library -->
            <button
              @click="openMealsLibraryModal"
              class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
              title="Meine Mahlzeiten"
            >
              <span class="text-lg">📖</span>
            </button>

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
              <SuggestionsCard
                :suggestions="getSuggestions(day.date, 'breakfast')"
                :isParent="isParent"
                @approve="approveSuggestion"
                @reject="rejectSuggestion"
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
              <SuggestionsCard
                :suggestions="getSuggestions(day.date, 'lunch')"
                :isParent="isParent"
                @approve="approveSuggestion"
                @reject="rejectSuggestion"
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
              <SuggestionsCard
                :suggestions="getSuggestions(day.date, 'dinner')"
                :isParent="isParent"
                @approve="approveSuggestion"
                @reject="rejectSuggestion"
              />
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Add meal modal (different for kids and parents) -->
    <div v-if="showAddMealModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white dark:bg-[var(--bg-secondary)] rounded-lg max-w-md w-full p-6 max-h-[80vh] overflow-y-auto">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
          {{ isKid ? 'Mahlzeit vorschlagen' : 'Mahlzeit hinzufügen' }}
        </h2>

        <!-- Kids: Select from meals library -->
        <div v-if="isKid">
          <div v-if="mealsLibrary.length > 0" class="space-y-2 mb-4">
            <button
              v-for="(meal, index) in mealsLibrary"
              :key="index"
              @click="selectMealFromLibrary(meal.title)"
              class="w-full text-left p-3 bg-gray-50 dark:bg-[var(--bg-primary)] hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
            >
              <div class="font-medium text-gray-900 dark:text-white">
                {{ meal.title }}
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                {{ meal.usage_count }}x verwendet
              </div>
            </button>
          </div>
          <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
            Noch keine Mahlzeiten gespeichert
          </div>

          <button
            @click="closeAddMealModal"
            class="w-full bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500 text-gray-900 dark:text-white px-4 py-2 rounded-lg"
          >
            Abbrechen
          </button>
        </div>

        <!-- Parents: Input field with autocomplete -->
        <form v-else @submit.prevent="addMeal">
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
                @input="handleMealSearch"
              />

              <!-- Autocomplete suggestions -->
              <div v-if="mealSuggestions.length > 0" class="mt-2 bg-white dark:bg-[var(--bg-primary)] border border-gray-300 dark:border-gray-600 rounded-lg max-h-40 overflow-y-auto">
                <button
                  v-for="(suggestion, index) in mealSuggestions"
                  :key="index"
                  type="button"
                  @click="selectMealSuggestion(suggestion)"
                  class="w-full text-left px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-900 dark:text-white text-sm border-b border-gray-200 dark:border-gray-700 last:border-b-0"
                >
                  {{ suggestion }}
                </button>
              </div>
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

    <!-- Meals Library modal -->
    <div v-if="showMealsLibraryModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
      <div class="bg-white dark:bg-[var(--bg-secondary)] rounded-lg max-w-2xl w-full p-6 max-h-[80vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white">
            Meine Mahlzeiten
          </h2>
          <button
            @click="closeMealsLibraryModal"
            class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
          >
            ✕
          </button>
        </div>

        <!-- Meals list -->
        <div v-if="mealsLibrary.length > 0" class="space-y-2">
          <div
            v-for="(meal, index) in mealsLibrary"
            :key="index"
            class="p-3 bg-gray-50 dark:bg-[var(--bg-primary)] rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            <div class="flex items-center justify-between">
              <div class="flex-1">
                <h3 class="font-medium text-gray-900 dark:text-white">
                  {{ meal.title }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                  {{ meal.usage_count }}x verwendet · Zuletzt: {{ formatDate(meal.last_used) }}
                </p>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
          Noch keine Mahlzeiten gespeichert
        </div>
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
import { Link, usePage } from '@inertiajs/vue3';
import { useMealPlansStore } from '../Stores/mealPlans';
import { useSuggestionsStore } from '../Stores/suggestions';
import { useItemsStore } from '../Stores/items';
import { useTheme } from '../Composables/useTheme';
import { useToast } from '../Composables/useToast';
import MealCard from '../Components/MealCard.vue';
import SuggestionsCard from '../Components/SuggestionsCard.vue';

const mealPlansStore = useMealPlansStore();
const suggestionsStore = useSuggestionsStore();
const itemsStore = useItemsStore();
const { isDark, toggleTheme } = useTheme();
const { toasts, success, error: showError } = useToast();

// Get current user from Inertia page props
const page = usePage();
const currentUser = computed(() => page.props.auth.user);
const isKid = computed(() => currentUser.value?.role === 'kid');
const isParent = computed(() => currentUser.value?.role === 'parent');

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
const mealSuggestions = ref([]);
let mealSearchTimeout = null;

// Meal details modal
const showMealDetailsModal = ref(false);
const selectedMeal = ref(null);

// Meals library modal
const showMealsLibraryModal = ref(false);
const mealsLibrary = ref([]);

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
  // Use local date parts to avoid timezone issues
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
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

function getSuggestions(date, mealType) {
  return suggestionsStore.getSuggestionsFor(date, mealType);
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
async function openAddMealModal(date, mealType) {
  addMealDate.value = date;
  addMealType.value = mealType;
  newMealTitle.value = '';
  showAddMealModal.value = true;

  // Load meals library for kids
  if (isKid.value) {
    try {
      mealsLibrary.value = await mealPlansStore.fetchMealsLibrary();
    } catch (err) {
      showError('Fehler beim Laden der Mahlzeiten');
    }
  }
}

function closeAddMealModal() {
  showAddMealModal.value = false;
  newMealTitle.value = '';
  mealSuggestions.value = [];
}

// Kids: Select meal from library and create suggestion
async function selectMealFromLibrary(mealTitle) {
  try {
    await suggestionsStore.createSuggestion({
      date: addMealDate.value,
      meal_type: addMealType.value,
      title: mealTitle,
    });

    success('Vorschlag erstellt');
    closeAddMealModal();
  } catch (err) {
    showError('Fehler beim Erstellen des Vorschlags');
  }
}

// Suggestions management
async function approveSuggestion(suggestionId) {
  try {
    const result = await suggestionsStore.approveSuggestion(suggestionId);
    success('Vorschlag genehmigt');

    // Refresh meal plans to show the newly created meal
    await loadMealPlans();
  } catch (err) {
    showError('Fehler beim Genehmigen des Vorschlags');
  }
}

async function rejectSuggestion(suggestionId) {
  try {
    await suggestionsStore.rejectSuggestion(suggestionId);
    success('Vorschlag abgelehnt');
  } catch (err) {
    showError('Fehler beim Ablehnen des Vorschlags');
  }
}

async function handleMealSearch() {
  if (newMealTitle.value.length < 2) {
    mealSuggestions.value = [];
    return;
  }

  clearTimeout(mealSearchTimeout);
  mealSearchTimeout = setTimeout(async () => {
    try {
      mealSuggestions.value = await mealPlansStore.searchMeals(newMealTitle.value);
    } catch (err) {
      console.error('Error searching meals:', err);
    }
  }, 300);
}

function selectMealSuggestion(suggestion) {
  newMealTitle.value = suggestion;
  mealSuggestions.value = [];
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

// Meals library
async function openMealsLibraryModal() {
  try {
    mealsLibrary.value = await mealPlansStore.fetchMealsLibrary();
    showMealsLibraryModal.value = true;
  } catch (err) {
    showError('Fehler beim Laden der Mahlzeiten');
  }
}

function closeMealsLibraryModal() {
  showMealsLibraryModal.value = false;
}

function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString('de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

// Load meal plans and suggestions
async function loadMealPlans() {
  await mealPlansStore.fetchMealPlans(formatDateForAPI(currentWeekStart.value));
  await suggestionsStore.fetchSuggestions(formatDateForAPI(currentWeekStart.value));
}

onMounted(() => {
  loadMealPlans();
});
</script>
