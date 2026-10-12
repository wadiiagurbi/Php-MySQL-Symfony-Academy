import { defineStore } from 'pinia'

export const useThemeStore = defineStore('theme', {
  state: () => ({
    isDark: false,
  }),
  actions: {
    toggle() {
      this.isDark = !this.isDark
      try {
        localStorage.setItem('theme-dark', this.isDark ? '1' : '0')
      } catch {
        // localStorage unavailable (private mode, etc.) — ignore
      }
    },
    restore() {
      try {
        this.isDark = localStorage.getItem('theme-dark') === '1'
      } catch {
        this.isDark = false
      }
    },
  },
})
