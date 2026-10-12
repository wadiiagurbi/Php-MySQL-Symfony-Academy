<script setup>
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useCoursesStore } from '../stores/courses'

const coursesStore = useCoursesStore()
const route = useRoute()

const search = ref('')

const currentSlug = computed(() => route.params.slug)

const filteredTracks = computed(() => {
  const query = search.value.trim().toLowerCase()
  if (!query) {
    return coursesStore.tracks
  }
  return coursesStore.tracks
    .map((track) => ({
      ...track,
      chapters: track.chapters
        .map((chapter) => ({
          ...chapter,
          lessons: chapter.lessons.filter((lesson) => lesson.title.toLowerCase().includes(query)),
        }))
        .filter((chapter) => chapter.lessons.length > 0),
    }))
    .filter((track) => track.chapters.length > 0)
})

function trackLessons(track) {
  return track.chapters.flatMap((chapter) => chapter.lessons)
}

function trackCompleted(track) {
  return trackLessons(track).filter((lesson) => coursesStore.isCompleted(lesson.slug)).length
}

function chapterCompleted(chapter) {
  return chapter.lessons.filter((lesson) => coursesStore.isCompleted(lesson.slug)).length
}

function chapterPercentage(chapter) {
  if (chapter.lessons.length === 0) {
    return 0
  }
  return Math.round((chapterCompleted(chapter) / chapter.lessons.length) * 100)
}
</script>

<template>
  <div class="sidebar-content">
    <div class="pa-3 pb-1">
      <v-text-field
        v-model="search"
        density="compact"
        variant="outlined"
        prepend-inner-icon="mdi-magnify"
        label="Filter lessons"
        aria-label="Filter lessons"
        hide-details
        clearable
        single-line
      />
    </div>

    <div v-if="coursesStore.loading && !coursesStore.loaded" class="pa-4">
      <v-progress-linear indeterminate color="primary" aria-label="Loading course content" />
    </div>

    <v-alert
      v-else-if="coursesStore.error"
      type="error"
      variant="tonal"
      class="ma-2"
      density="compact"
      :text="coursesStore.error"
    />

    <p v-else-if="filteredTracks.length === 0" class="text-body-2 text-medium-emphasis pa-4">
      No lessons match &ldquo;{{ search }}&rdquo;.
    </p>

    <v-list v-else density="compact" nav open-strategy="multiple" aria-label="Lessons by track">
      <template v-for="track in filteredTracks" :key="track.key">
        <v-list-subheader class="sidebar-track-header">
          <span class="sidebar-track-title">{{ track.title }}</span>
          <span class="sidebar-track-count text-caption text-medium-emphasis">
            {{ trackCompleted(track) }}/{{ trackLessons(track).length }}
          </span>
        </v-list-subheader>

        <v-list-group v-for="chapter in track.chapters" :key="chapter.key" :value="chapter.key">
          <template #activator="{ props: activatorProps }">
            <div class="sidebar-chapter">
              <v-list-item
                v-bind="activatorProps"
                :title="chapter.title"
                :subtitle="`${chapterCompleted(chapter)}/${chapter.lessons.length} complete`"
                density="compact"
              />
              <v-progress-linear
                :model-value="chapterPercentage(chapter)"
                color="primary"
                height="4"
                rounded
                class="sidebar-chapter-progress"
                :aria-label="`${chapter.title}: ${chapterPercentage(chapter)} percent complete`"
              />
            </div>
          </template>

          <v-list-item
            v-for="lesson in chapter.lessons"
            :key="lesson.slug"
            :to="{ name: 'lesson', params: { slug: lesson.slug } }"
            :active="lesson.slug === currentSlug"
            active-class="active-lesson"
            :aria-current="lesson.slug === currentSlug ? 'page' : undefined"
            density="compact"
            class="sidebar-lesson"
          >
            <template #prepend>
              <v-icon
                size="18"
                :color="coursesStore.isCompleted(lesson.slug) ? 'success' : undefined"
                :icon="coursesStore.isCompleted(lesson.slug) ? 'mdi-check-circle' : 'mdi-circle-outline'"
                :aria-label="coursesStore.isCompleted(lesson.slug) ? 'Completed' : undefined"
              />
            </template>
            <v-list-item-title class="text-body-2">{{ lesson.title }}</v-list-item-title>
          </v-list-item>
        </v-list-group>
      </template>
    </v-list>
  </div>
</template>

<style scoped>
.sidebar-content {
  height: 100%;
  overflow-y: auto;
}

.sidebar-track-header {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 8px;
}

.sidebar-track-title {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.sidebar-track-count {
  flex: 0 0 auto;
  font-variant-numeric: tabular-nums;
}

.sidebar-chapter {
  padding-bottom: 4px;
}

.sidebar-chapter-progress {
  margin: 0 16px;
  max-width: calc(100% - 32px);
}

.sidebar-lesson.active-lesson {
  background: rgba(79, 70, 229, 0.1);
  border-inline-start: 3px solid #4F46E5;
  font-weight: 600;
}
</style>
