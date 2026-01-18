<template>
  <div class="min-h-screen bg-gray-50 dark:bg-[var(--bg-primary)]">
    <!-- Header -->
    <header class="bg-white dark:bg-[var(--bg-secondary)] shadow-sm sticky top-0 z-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <Link v-if="isParent" href="/" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700">
              ←
            </Link>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white">
              🍱 Lunchbox
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

            <!-- Meal Planner -->
            <Link
              href="/meal-planner"
              class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
              title="Essensplaner"
            >
              <span class="text-lg">📅</span>
            </Link>

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
      <div v-if="lunchboxStore.loading" class="text-center py-8">
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

          <!-- Lunchbox cards -->
          <div class="space-y-4">
            <!-- For kids: Show their own lunchbox -->
            <div v-if="isKid">
              <LunchboxCard
                :date="day.date"
                :userId="currentUser.id"
                :items="getItemsForDateAndUser(day.date, currentUser.id)"
                :canEdit="true"
                :showUserName="false"
              />
            </div>

            <!-- For parents: Show all children's lunchboxes -->
            <div v-else-if="isParent" class="space-y-4">
              <div v-if="children.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                Keine Kinder gefunden
              </div>
              <div v-else>
                <LunchboxCard
                  v-for="child in children"
                  :key="child.id"
                  :date="day.date"
                  :userId="child.id"
                  :userName="child.name"
                  :items="getItemsForDateAndUser(day.date, child.id)"
                  :canEdit="false"
                  :showUserName="true"
                />
              </div>
            </div>

            <!-- Fallback for users without role -->
            <div v-else class="text-center py-8 text-gray-500 dark:text-gray-400">
              Keine Berechtigung
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useLunchboxStore } from '../Stores/lunchbox';
import { useTheme } from '../Composables/useTheme';
import LunchboxCard from '../Components/LunchboxCard.vue';

const lunchboxStore = useLunchboxStore();
const { isDark, toggleTheme } = useTheme();

// Get current user and children from Inertia page props
const page = usePage();
const currentUser = computed(() => page.props.auth.user);
const children = computed(() => page.props.auth.user?.children || []);
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

function getItemsForDateAndUser(date, userId) {
  return lunchboxStore.getItemsForDateAndUser(date, userId);
}

// Week navigation
function previousWeek() {
  const newStart = new Date(currentWeekStart.value);
  newStart.setDate(newStart.getDate() - 7);
  currentWeekStart.value = newStart;
  loadLunchboxItems();
}

function nextWeek() {
  const newStart = new Date(currentWeekStart.value);
  newStart.setDate(newStart.getDate() + 7);
  currentWeekStart.value = newStart;
  loadLunchboxItems();
}

// Load lunchbox items
async function loadLunchboxItems() {
  const startDate = formatDateForAPI(currentWeekStart.value);
  await lunchboxStore.fetchLunchboxItems(startDate);
}

// Initialize
onMounted(() => {
  loadLunchboxItems();

  // Subscribe to real-time updates if user is a parent
  if (isParent.value && currentUser.value?.id) {
    lunchboxStore.subscribeToUpdates(currentUser.value.id);
  }
});

// Cleanup
onUnmounted(() => {
  // Unsubscribe from real-time updates
  lunchboxStore.unsubscribeFromUpdates();
});

// Watch for week changes
watch(currentWeekStart, () => {
  loadLunchboxItems();
});
</script>
