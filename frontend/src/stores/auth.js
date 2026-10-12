import { defineStore } from 'pinia'
import apiClient, { setAuthToken } from '../api/client'

const STORAGE_KEY = 'academy-auth-token'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: null,
    user: null,
    ready: false,
  }),
  getters: {
    isAuthenticated: (state) => null !== state.token,
  },
  actions: {
    async init() {
      let token = null
      try {
        token = localStorage.getItem(STORAGE_KEY)
      } catch {
        token = null
      }

      if (token) {
        this.token = token
        setAuthToken(token)
        try {
          await this.fetchMe()
        } catch {
          this.logout()
        }
      }

      this.ready = true
    },

    async login(email, password) {
      const { data } = await apiClient.post('/auth/login', { email, password })
      this.setToken(data.token)
      await this.fetchMe()
    },

    async register(email, password, displayName) {
      await apiClient.post('/auth/register', { email, password, displayName })
      await this.login(email, password)
    },

    async fetchMe() {
      const { data } = await apiClient.get('/me')
      this.user = data
    },

    setToken(token) {
      this.token = token
      setAuthToken(token)
      try {
        localStorage.setItem(STORAGE_KEY, token)
      } catch {
        // localStorage unavailable — session simply won't persist across reloads.
      }
    },

    logout() {
      this.token = null
      this.user = null
      setAuthToken(null)
      try {
        localStorage.removeItem(STORAGE_KEY)
      } catch {
        // ignore
      }
    },
  },
})
