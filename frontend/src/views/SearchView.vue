<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import apiClient from '../api/client'
import DifficultyBadge from '../components/DifficultyBadge.vue'

const route = useRoute()
const router = useRouter()

const query = ref(route.query.q || '')
const results = ref([])
const loading = ref(false)
const searched = ref(false)
const error = ref(null)

async function runSearch() {
  if (!query.value.trim()) {
    results.value = []
    searched.value = false
    error.value = null
    return
  }

  loading.value = true
  searched.value = true
  error.value = null
  router.replace({ name: 'search', query: { q: query.value } })

  try {
    const { data } = await apiClient.get('/search', { params: { q: query.value } })
    results.value = data.results
  } catch (searchError) {
    error.value = searchError.response?.data?.error?.message || 'Could not run the search. Please try again.'
    results.value = []
  } finally {
    loading.value = false
  }
}

if (query.value) {
  runSearch()
}
</script>

<template>
  <v-container class="py-6" style="max-width: 760px">
    <h1 class="text-h5 mb-4">Search the course</h1>

    <v-text-field
      v-model="query"
      label="Search lessons by title, chapter, or content"
      prepend-inner-icon="mdi-magnify"
      clearable
      aria-label="Search lessons"
      @keyup.enter="runSearch"
      @click:clear="results = []; searched = false; error = null"
    >
      <template #append>
        <v-btn color="primary" @click="runSearch">Search</v-btn>
      </template>
    </v-text-field>

    <div v-if="loading" class="text-center py-8" role="status" aria-live="polite">
      <v-progress-circular indeterminate color="primary" />
      <span class="visually-hidden">Searching&hellip;</span>
    </div>

    <v-alert v-else-if="error" type="error" variant="tonal" class="mt-2" role="alert" aria-live="assertive" :text="error">
      <template #append>
        <v-btn size="small" variant="outlined" @click="runSearch">Retry</v-btn>
      </template>
    </v-alert>

    <v-alert v-else-if="searched && 0 === results.length" type="info" variant="tonal" text="No lessons matched your search." />

    <v-list v-else-if="results.length > 0" lines="two" aria-label="Search results">
      <v-list-item
        v-for="result in results"
        :key="result.lesson.slug"
        :to="{ name: 'lesson', params: { slug: result.lesson.slug } }"
        class="mb-2"
        rounded="lg"
        border
      >
        <v-list-item-title class="d-flex align-center ga-2">
          {{ result.lesson.title }}
          <DifficultyBadge :difficulty="result.lesson.difficulty" size="x-small" />
        </v-list-item-title>
        <v-list-item-subtitle>{{ result.lesson.chapterTitle }}</v-list-item-subtitle>
        <p class="text-body-2 text-medium-emphasis mt-1">{{ result.excerpt }}</p>
      </v-list-item>
    </v-list>
  </v-container>
</template>
