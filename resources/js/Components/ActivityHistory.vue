<template>
  <div>
    <!-- History Button Trigger -->
    <button
      @click="openModal"
      class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
      title="Aktivitätsverlauf"
    >
      <span class="text-lg">📜</span>
    </button>

    <!-- Modal Overlay -->
    <Transition name="fade">
      <div
        v-if="isOpen"
        class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
        @click.self="closeModal"
      >
        <!-- Modal Content -->
        <div
          class="bg-white dark:bg-[var(--bg-secondary)] rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] flex flex-col"
          @click.stop
        >
          <!-- Header -->
          <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
              📜 Aktivitätsverlauf
            </h2>
            <button
              @click="closeModal"
              class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 text-gray-500 dark:text-gray-400"
            >
              ✕
            </button>
          </div>

          <!-- Activity List -->
          <div class="flex-1 overflow-y-auto p-4">
            <!-- Loading State -->
            <div v-if="loading && activities.length === 0" class="text-center py-8">
              <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900 dark:border-white"></div>
              <p class="mt-2 text-gray-600 dark:text-gray-400">Laden...</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="activities.length === 0" class="text-center py-8">
              <span class="text-4xl">📭</span>
              <p class="mt-2 text-gray-600 dark:text-gray-400">
                Noch keine Aktivitäten
              </p>
            </div>

            <!-- Activity Timeline -->
            <div v-else class="space-y-4">
              <div
                v-for="activity in activities"
                :key="activity.id"
                class="flex gap-3 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
              >
                <!-- User Avatar -->
                <div
                  v-if="activity.user"
                  class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-white font-bold"
                  :style="{ backgroundColor: activity.user.avatar_color }"
                >
                  {{ activity.user.name.charAt(0) }}
                </div>
                <div
                  v-else
                  class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center bg-gray-300 dark:bg-gray-600 text-gray-600 dark:text-gray-300"
                >
                  {{ activity.icon }}
                </div>

                <!-- Activity Content -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-start gap-2">
                    <span class="text-lg flex-shrink-0">{{ activity.icon }}</span>
                    <div class="flex-1">
                      <p class="text-sm text-gray-900 dark:text-white">
                        {{ activity.description }}
                      </p>
                      <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ activity.time_ago }}
                      </p>
                      <!-- Metadata (if any) -->
                      <div v-if="activity.metadata" class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                        <span v-if="activity.metadata.category">
                          Kategorie: {{ activity.metadata.category }}
                        </span>
                        <span v-if="activity.metadata.changes">
                          Änderungen: {{ formatChanges(activity.metadata.changes) }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Load More Button -->
              <div v-if="hasMore" class="text-center py-4">
                <button
                  @click="loadMore"
                  :disabled="loading"
                  class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span v-if="loading">Laden...</span>
                  <span v-else>Mehr laden</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useActivitiesStore } from '../Stores/activities';

const activitiesStore = useActivitiesStore();
const isOpen = ref(false);

const activities = computed(() => activitiesStore.activities);
const loading = computed(() => activitiesStore.loading);
const hasMore = computed(() => activitiesStore.hasMore);

const openModal = async () => {
  isOpen.value = true;
  // Fetch activities when modal opens
  if (activities.value.length === 0) {
    await activitiesStore.fetchActivities();
  }
};

const closeModal = () => {
  isOpen.value = false;
};

const loadMore = async () => {
  await activitiesStore.loadMore();
};

const formatChanges = (changes) => {
  if (!changes || typeof changes !== 'object') return '';
  return Object.entries(changes)
    .map(([key, value]) => `${key}: ${value}`)
    .join(', ');
};

// Close modal on Escape key
watch(isOpen, (value) => {
  if (value) {
    const handleEscape = (e) => {
      if (e.key === 'Escape') {
        closeModal();
      }
    };
    document.addEventListener('keydown', handleEscape);
    return () => document.removeEventListener('keydown', handleEscape);
  }
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
