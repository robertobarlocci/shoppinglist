<template>
  <div class="min-h-screen bg-gray-50 dark:bg-[var(--bg-primary)]">
    <!-- Header -->
    <header class="bg-white dark:bg-[var(--bg-secondary)] shadow-sm sticky top-0 z-10">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <a href="/" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
              ← Zurück
            </a>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
              Einstellungen
            </h1>
          </div>

          <div class="flex items-center gap-2">
            <div
              class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold"
              :style="{ backgroundColor: $page.props.auth.user.avatar_color }"
            >
              {{ $page.props.auth.user.name.charAt(0) }}
            </div>
            <span class="text-sm text-gray-600 dark:text-gray-400">{{ $page.props.auth.user.name }}</span>
          </div>
        </div>
      </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="space-y-6">
        <!-- Password Change Section -->
        <section class="card p-6">
          <h2 class="text-xl font-bold mb-4 dark:text-white">Passwort ändern</h2>

          <form @submit.prevent="updatePassword" class="space-y-4">
            <div>
              <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Aktuelles Passwort
              </label>
              <input
                id="current_password"
                v-model="passwordForm.current_password"
                type="password"
                class="input"
                required
              />
              <div v-if="passwordForm.errors.current_password" class="text-red-500 text-sm mt-1">
                {{ passwordForm.errors.current_password }}
              </div>
            </div>

            <div>
              <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Neues Passwort
              </label>
              <input
                id="password"
                v-model="passwordForm.password"
                type="password"
                class="input"
                required
              />
              <div v-if="passwordForm.errors.password" class="text-red-500 text-sm mt-1">
                {{ passwordForm.errors.password }}
              </div>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Mindestens 8 Zeichen
              </p>
            </div>

            <div>
              <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Neues Passwort bestätigen
              </label>
              <input
                id="password_confirmation"
                v-model="passwordForm.password_confirmation"
                type="password"
                class="input"
                required
              />
            </div>

            <div class="flex items-center gap-4">
              <button
                type="submit"
                :disabled="passwordForm.processing"
                class="btn btn-primary"
              >
                <span v-if="passwordForm.processing">Wird gespeichert...</span>
                <span v-else>Passwort ändern</span>
              </button>

              <div v-if="passwordForm.recentlySuccessful" class="text-green-600 dark:text-green-400 text-sm">
                ✓ Passwort erfolgreich geändert
              </div>
            </div>
          </form>
        </section>

        <!-- User Information Section -->
        <section class="card p-6">
          <h2 class="text-xl font-bold mb-4 dark:text-white">Benutzerinformationen</h2>

          <div class="space-y-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
              <p class="text-gray-900 dark:text-white">{{ $page.props.auth.user.name }}</p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">E-Mail</label>
              <p class="text-gray-900 dark:text-white">{{ $page.props.auth.user.email }}</p>
            </div>
          </div>
        </section>
      </div>
    </main>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';

const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const updatePassword = () => {
  passwordForm.put(route('profile.password.update'), {
    preserveScroll: true,
    onSuccess: () => {
      passwordForm.reset();
    },
  });
};
</script>
