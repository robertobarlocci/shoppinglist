<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-[var(--bg-primary)] py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div>
        <h1 class="text-center text-4xl font-bold text-gray-900 dark:text-white">
          🛒 Shopping List
        </h1>
        <h2 class="mt-2 text-center text-xl text-gray-600 dark:text-gray-400">
          Anmelden
        </h2>
      </div>

      <form class="mt-8 space-y-6" @submit.prevent="submit">
        <div class="rounded-md shadow-sm -space-y-px">
          <div>
            <label for="email" class="sr-only">E-Mail</label>
            <input
              id="email"
              v-model="form.email"
              name="email"
              type="email"
              autocomplete="email"
              required
              class="input rounded-t-md rounded-b-none"
              placeholder="E-Mail Adresse"
            />
          </div>
          <div>
            <label for="password" class="sr-only">Passwort</label>
            <input
              id="password"
              v-model="form.password"
              name="password"
              type="password"
              autocomplete="current-password"
              required
              class="input rounded-b-md rounded-t-none"
              placeholder="Passwort"
            />
          </div>
        </div>

        <div v-if="form.errors.email" class="text-red-500 text-sm">
          {{ form.errors.email }}
        </div>

        <div>
          <button
            type="submit"
            :disabled="form.processing"
            class="btn btn-primary w-full"
          >
            <span v-if="form.processing">Wird angemeldet...</span>
            <span v-else>Anmelden</span>
          </button>
        </div>
      </form>

      <div class="text-center text-sm text-gray-600 dark:text-gray-400">
        <p>Demo-Accounts:</p>
        <p class="mt-1">fritz@example.com / password</p>
        <p>vreni@example.com / password</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
  email: '',
  password: '',
});

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  });
};
</script>
