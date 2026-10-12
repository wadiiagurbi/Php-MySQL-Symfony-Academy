import { defineStore } from 'pinia'
import apiClient from '../api/client'

export const useCoursesStore = defineStore('courses', {
  state: () => ({
    tracks: [],
    completedLessonSlugs: [],
    loading: false,
    error: null,
    loaded: false,
  }),
  getters: {
    totalLessons: (state) => state.tracks.reduce(
      (trackSum, track) => trackSum + track.chapters.reduce((chapterSum, chapter) => chapterSum + chapter.lessons.length, 0),
      0,
    ),
    completionPercentage(state) {
      if (0 === this.totalLessons) {
        return 0
      }

      return Math.round((state.completedLessonSlugs.length / this.totalLessons) * 100)
    },
    isCompleted: (state) => (slug) => state.completedLessonSlugs.includes(slug),
  },
  actions: {
    async load(force = false) {
      if (this.loaded && !force) {
        return
      }

      this.loading = true
      this.error = null
      try {
        const { data } = await apiClient.get('/courses')
        this.tracks = data.tracks
        this.completedLessonSlugs = data.completedLessonSlugs || []
        this.loaded = true
      } catch (error) {
        this.error = error.response?.data?.error?.message || 'Could not load the course content.'
      } finally {
        this.loading = false
      }
    },

    markCompleted(slug) {
      if (!this.completedLessonSlugs.includes(slug)) {
        this.completedLessonSlugs.push(slug)
      }
    },

    markIncomplete(slug) {
      this.completedLessonSlugs = this.completedLessonSlugs.filter((s) => s !== slug)
    },
  },
})
