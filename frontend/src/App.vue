<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useDisplay, useTheme } from 'vuetify'
import { useRoute, useRouter } from 'vue-router'
import { useThemeStore } from './stores/theme'
import { useAuthStore } from './stores/auth'
import { useCoursesStore } from './stores/courses'
import AppSidebar from './components/AppSidebar.vue'

const vuetifyTheme = useTheme()
const display = useDisplay()
const themeStore = useThemeStore()
const authStore = useAuthStore()
const coursesStore = useCoursesStore()
const router = useRouter()
const route = useRoute()

// Drawer: persistent 300px panel on desktop, temporary overlay on mobile.
const drawerOpen = ref(true)
const isMobile = computed(() => display.smAndDown.value)

watch(isMobile, (mobile) => {
  drawerOpen.value = !mobile
}, { immediate: true })

// Close the temporary drawer after navigation on mobile.
watch(() => route.path, () => {
  if (isMobile.value) {
    drawerOpen.value = false
  }
})

const completedCount = computed(() => coursesStore.completedLessonSlugs.length)
const totalCount = computed(() => coursesStore.totalLessons)
const completionPercentage = computed(() => coursesStore.completionPercentage)

onMounted(async () => {
  themeStore.restore()
  vuetifyTheme.change(themeStore.isDark ? 'dark' : 'light')
  await authStore.init()
  coursesStore.load()
})

function toggleTheme() {
  themeStore.toggle()
  vuetifyTheme.change(themeStore.isDark ? 'dark' : 'light')
}

function toggleDrawer() {
  drawerOpen.value = !drawerOpen.value
}

function logout() {
  authStore.logout()
  router.push({ name: 'dashboard' })
}
</script>

<template>
  <v-app>
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <v-app-bar flat border height="64">
      <v-app-bar-nav-icon
        aria-label="Toggle course navigation"
        :aria-expanded="drawerOpen ? 'true' : 'false'"
        @click="toggleDrawer"
      />
      <v-app-bar-title>
        <router-link :to="{ name: 'dashboard' }" class="app-title-link">PHP &amp; Symfony Academy</router-link>
      </v-app-bar-title>
      <v-spacer />
      <v-btn icon="mdi-magnify" :to="{ name: 'search' }" aria-label="Search lessons" />
      <v-btn
        :icon="themeStore.isDark ? 'mdi-weather-sunny' : 'mdi-weather-night'"
        :aria-label="themeStore.isDark ? 'Switch to light mode' : 'Switch to dark mode'"
        @click="toggleTheme"
      />

      <template v-if="authStore.isAuthenticated">
        <v-btn variant="text" :to="{ name: 'tasks' }" class="d-none d-sm-flex">Task Manager</v-btn>
        <v-btn variant="text" @click="logout">Log out</v-btn>
      </template>
      <template v-else>
        <v-btn variant="text" :to="{ name: 'login' }">Log in</v-btn>
        <v-btn color="primary" variant="flat" :to="{ name: 'register' }" class="mr-2">Sign up</v-btn>
      </template>
    </v-app-bar>

    <v-navigation-drawer
      v-model="drawerOpen"
      width="300"
      :temporary="isMobile"
      :permanent="!isMobile"
      aria-label="Course navigation"
    >
      <AppSidebar />
    </v-navigation-drawer>

    <v-main>
      <div id="main-content" tabindex="-1" class="main-content">
        <router-view />
      </div>
    </v-main>

    <v-footer app border class="app-footer">
      <span class="text-caption text-medium-emphasis">PHP &amp; Symfony Academy</span>
      <v-spacer />
      <span
        v-if="totalCount > 0"
        class="text-caption text-medium-emphasis"
        aria-live="polite"
      >
        {{ completedCount }} / {{ totalCount }} lessons &middot; {{ completionPercentage }}%
      </span>
      <span v-else class="text-caption text-medium-emphasis">Loading course progress&hellip;</span>
    </v-footer>
  </v-app>
</template>

<style>
html {
  scroll-behavior: smooth;
}

.visually-hidden {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

.skip-link {
  position: absolute;
  top: -48px;
  left: 12px;
  z-index: 2000;
  padding: 8px 16px;
  border-radius: 8px;
  background: #4F46E5;
  color: #F5F5F7;
  font-size: 0.875rem;
  font-weight: 600;
  text-decoration: none;
  transition: top 0.15s ease;
}

.skip-link:focus-visible {
  top: 8px;
  outline: 2px solid #F5F5F7;
  outline-offset: 2px;
}

.main-content:focus {
  outline: none;
}

.app-title-link {
  color: inherit;
  text-decoration: none;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.app-footer {
  max-height: 40px;
}

@media (prefers-reduced-motion: reduce) {
  html {
    scroll-behavior: auto;
  }

  *,
  *::before,
  *::after {
    animation: none !important;
    transition: none !important;
  }
}
</style>
