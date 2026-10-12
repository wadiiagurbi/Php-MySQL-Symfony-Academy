<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref(null)

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()

async function submit() {
  loading.value = true
  error.value = null
  try {
    await authStore.login(email.value, password.value)
    router.push(route.query.redirect || { name: 'dashboard' })
  } catch (submitError) {
    error.value = submitError.response?.data?.message || submitError.response?.data?.error?.message || 'Invalid email or password.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <v-container class="py-10" style="max-width: 480px">
    <v-card elevation="2">
      <v-card-item>
        <v-card-title>Log in</v-card-title>
        <v-card-subtitle>Continue your PHP &amp; Symfony learning journey</v-card-subtitle>
      </v-card-item>
      <v-card-text>
        <v-form @submit.prevent="submit">
          <v-text-field v-model="email" label="Email" type="email" required autocomplete="email" />
          <v-text-field v-model="password" label="Password" type="password" required autocomplete="current-password" />
          <v-alert v-if="error" type="error" variant="tonal" density="compact" class="mb-4" role="alert" aria-live="assertive" :text="error" />
          <v-btn type="submit" color="primary" block :loading="loading">Log in</v-btn>
        </v-form>
        <p class="text-body-2 mt-4 text-center">
          Don't have an account?
          <router-link :to="{ name: 'register' }">Create one</router-link>
        </p>
      </v-card-text>
    </v-card>
  </v-container>
</template>
