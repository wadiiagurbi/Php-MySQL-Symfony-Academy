<script setup>
</script>

<template>
  <v-container class="py-16 text-center">
    <v-icon icon="mdi-compass-off-outline" size="64" color="grey" class="mb-4" />
    <h1 class="text-h5 mb-2">Page not found</h1>
    <p class="text-body-2 text-medium-emphasis mb-6">The page you're looking for doesn't exist.</p>
    <v-btn color="primary" :to="{ name: 'dashboard' }">Back to Dashboard</v-btn>
  </v-container>
</template>
