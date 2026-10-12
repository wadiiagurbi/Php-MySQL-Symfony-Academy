<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const email = ref('')
const password = ref('')
const displayName = ref('')
const loading = ref(false)
const error = ref(null)

const authStore = useAuthStore()
const router = useRouter()

async function submit() {
  loading.value = true
  error.value = null
  try {
    await authStore.register(email.value, password.value, displayName.value)
    router.push({ name: 'dashboard' })
  } catch (submitError) {
    const data = submitError.response?.data
    if (data?.error?.details) {
      error.value = data.error.details.map((d) => d.message).join(' ')
    } else {
      error.value = data?.error?.message || 'Could not create your account.'
    }
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <v-container class="py-10" style="max-width: 480px">
    <v-card elevation="2">
      <v-card-item>
        <v-card-title>Create your account</v-card-title>
        <v-card-subtitle>Track your progress across the whole course</v-card-subtitle>
      </v-card-item>
      <v-card-text>
        <v-form @submit.prevent="submit">
          <v-text-field v-model="displayName" label="Display name" required autocomplete="name" />
          <v-text-field v-model="email" label="Email" type="email" required autocomplete="email" />
          <v-text-field v-model="password" label="Password" type="password" required hint="At least 8 characters" autocomplete="new-password" />
          <v-alert v-if="error" type="error" variant="tonal" density="compact" class="mb-4" role="alert" aria-live="assertive" :text="error" />
          <v-btn type="submit" color="primary" block :loading="loading">Create account</v-btn>
        </v-form>
        <p class="text-body-2 mt-4 text-center">
          Already have an account?
          <router-link :to="{ name: 'login' }">Log in</router-link>
        </p>
      </v-card-text>
    </v-card>
  </v-container>
</template>
