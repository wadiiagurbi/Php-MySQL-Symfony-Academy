<script setup>
import { computed, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import apiClient from '../api/client'
import { useAuthStore } from '../stores/auth'
import { useCoursesStore } from '../stores/courses'
import MarkdownContent from '../components/MarkdownContent.vue'
import CodeBlock from '../components/CodeBlock.vue'
import DifficultyBadge from '../components/DifficultyBadge.vue'
import QuizWidget from '../components/QuizWidget.vue'
import ProgressBar from '../components/ProgressBar.vue'

const props = defineProps({
  slug: { type: String, required: true },
})

const router = useRouter()
const authStore = useAuthStore()
const coursesStore = useCoursesStore()

const lesson = ref(null)
const loading = ref(true)
const error = ref(null)
const solutionVisible = ref(false)
const togglingCompletion = ref(false)

const isCompleted = computed(() => coursesStore.isCompleted(props.slug))
const completedCount = computed(() => coursesStore.completedLessonSlugs.length)
const totalCount = computed(() => coursesStore.totalLessons)
const completionPercentage = computed(() => coursesStore.completionPercentage)

const tocItems = [
  { id: 'learning-objective', label: 'Learning Objective' },
  { id: 'explanation', label: 'Explanation' },
  { id: 'why-it-matters', label: 'Why It Matters' },
  { id: 'example', label: 'Example' },
  { id: 'code', label: 'Code' },
  { id: 'code-explanation', label: 'Code Explanation' },
  { id: 'common-mistakes', label: 'Common Mistakes' },
  { id: 'best-practices', label: 'Best Practices' },
  { id: 'exercise', label: 'Exercise' },
  { id: 'solution', label: 'Solution' },
  { id: 'quiz', label: 'Mini Quiz' },
]

async function loadLesson(slug) {
  loading.value = true
  error.value = null
  lesson.value = null
  solutionVisible.value = false

  try {
    const { data } = await apiClient.get(`/lessons/${slug}`)
    lesson.value = data
  } catch (loadError) {
    if (404 === loadError.response?.status) {
      error.value = 'This lesson could not be found. It may have been moved or renamed.'
    } else {
      error.value = 'Something went wrong while loading this lesson. Please try again.'
    }
  } finally {
    loading.value = false
  }
}

async function toggleCompletion() {
  togglingCompletion.value = true
  try {
    if (isCompleted.value) {
      await apiClient.delete(`/lessons/${props.slug}/complete`)
      coursesStore.markIncomplete(props.slug)
    } else {
      await apiClient.post(`/lessons/${props.slug}/complete`)
      coursesStore.markCompleted(props.slug)
    }
  } catch {
    // If this fails (e.g. session expired), the UI simply keeps its previous state.
  } finally {
    togglingCompletion.value = false
  }
}

function goTo(slug) {
  router.push({ name: 'lesson', params: { slug } })
}

watch(() => props.slug, loadLesson, { immediate: true })
</script>

<template>
  <v-container class="lesson-wrap py-6">
    <div v-if="loading">
      <v-skeleton-loader type="heading, text, paragraph, paragraph, actions" class="mb-4" />
    </div>

    <v-alert v-else-if="error" type="error" variant="tonal" :text="error" />

    <v-row v-else-if="lesson">
      <v-col cols="12" md="8" lg="9">
        <article>
          <v-breadcrumbs :items="[lesson.trackTitle, lesson.chapterTitle, lesson.title]" density="compact" class="px-0" />

          <div class="d-flex align-center flex-wrap ga-2 mb-4">
            <h1 class="text-h4 mr-2">{{ lesson.title }}</h1>
            <DifficultyBadge :difficulty="lesson.difficulty" />
            <v-chip size="small" variant="outlined"><v-icon start size="16" icon="mdi-clock-outline" />{{ lesson.estimatedMinutes }} min</v-chip>
          </div>

          <!-- Collapsible mini-TOC on mobile -->
          <v-expansion-panels class="d-md-none mb-4">
            <v-expansion-panel title="On this page" rounded="xl">
              <v-expansion-panel-text>
                <nav aria-label="Lesson contents">
                  <ol class="toc-list">
                    <li v-for="item in tocItems" :key="item.id">
                      <a :href="`#${item.id}`" class="toc-link">{{ item.label }}</a>
                    </li>
                  </ol>
                </nav>
              </v-expansion-panel-text>
            </v-expansion-panel>
          </v-expansion-panels>

          <v-btn
            v-if="authStore.isAuthenticated"
            class="mb-6"
            :color="isCompleted ? 'success' : 'primary'"
            :variant="isCompleted ? 'flat' : 'outlined'"
            :loading="togglingCompletion"
            :prepend-icon="isCompleted ? 'mdi-check-circle' : 'mdi-circle-outline'"
            @click="toggleCompletion"
          >
            {{ isCompleted ? 'Completed' : 'Mark as complete' }}
          </v-btn>
          <v-alert v-else class="mb-6" type="info" variant="tonal" density="compact">
            <router-link :to="{ name: 'login' }">Log in</router-link> to track your progress and mark lessons as complete.
          </v-alert>

          <section id="learning-objective" class="mb-6 lesson-section">
            <h2 class="text-h6 mb-2">Learning Objective</h2>
            <MarkdownContent :source="lesson.sections.learningObjective" />
          </section>

          <section id="explanation" class="mb-6 lesson-section">
            <h2 class="text-h6 mb-2">Explanation</h2>
            <MarkdownContent :source="lesson.sections.explanation" />
          </section>

          <section id="why-it-matters" class="mb-6 lesson-section">
            <h2 class="text-h6 mb-2">Why It Matters</h2>
            <MarkdownContent :source="lesson.sections.whyItMatters" />
          </section>

          <section id="example" class="mb-6 lesson-section">
            <h2 class="text-h6 mb-2">Example</h2>
            <MarkdownContent :source="lesson.sections.example" />
          </section>

          <section id="code" class="mb-6 lesson-section">
            <h2 class="text-h6 mb-2">Code</h2>
            <CodeBlock :code="lesson.sections.code" :language="lesson.sections.codeLanguage" />
          </section>

          <section id="code-explanation" class="mb-6 lesson-section">
            <h2 class="text-h6 mb-2">Code Explanation</h2>
            <MarkdownContent :source="lesson.sections.codeExplanation" />
          </section>

          <section id="common-mistakes" class="mb-6 lesson-section">
            <h2 class="text-h6 mb-2"><v-icon size="20" icon="mdi-alert-circle-outline" color="warning" /> Common Mistakes</h2>
            <MarkdownContent :source="lesson.sections.commonMistakes" />
          </section>

          <section id="best-practices" class="mb-6 lesson-section">
            <h2 class="text-h6 mb-2"><v-icon size="20" icon="mdi-star-outline" color="success" /> Best Practices</h2>
            <MarkdownContent :source="lesson.sections.bestPractices" />
          </section>

          <v-divider class="my-6" />

          <section id="exercise" class="mb-6 lesson-section">
            <h2 class="text-h6 mb-2">Exercise</h2>
            <MarkdownContent :source="lesson.sections.exercise" />
          </section>

          <section id="solution" class="mb-6 lesson-section" aria-labelledby="solution-heading">
            <v-card variant="outlined" class="solution-card">
              <v-card-item>
                <v-card-title id="solution-heading">Solution</v-card-title>
                <v-card-subtitle>Try the exercise first — then compare approaches.</v-card-subtitle>
              </v-card-item>
              <v-card-actions>
                <v-btn
                  variant="tonal"
                  :prepend-icon="solutionVisible ? 'mdi-eye-off' : 'mdi-eye'"
                  :aria-expanded="solutionVisible ? 'true' : 'false'"
                  aria-controls="solution-panel"
                  @click="solutionVisible = !solutionVisible"
                >
                  {{ solutionVisible ? 'Hide Solution' : 'Show Solution' }}
                </v-btn>
              </v-card-actions>
              <v-expand-transition>
                <div v-show="solutionVisible" id="solution-panel" class="solution-panel">
                  <v-divider />
                  <div class="pa-4">
                    <MarkdownContent :source="lesson.sections.solution" />
                  </div>
                </div>
              </v-expand-transition>
            </v-card>
          </section>

          <v-divider class="my-6" />

          <section id="quiz" class="mb-10 lesson-section">
            <h2 class="text-h6 mb-2">Mini Quiz</h2>
            <QuizWidget :slug="lesson.slug" :questions="lesson.quiz" />
          </section>

          <div v-if="totalCount > 0" class="lesson-progress mb-4">
            <ProgressBar
              :value="completedCount"
              :total="totalCount"
              label="Overall course progress"
            />
            <p class="text-caption text-medium-emphasis mt-1 mb-0">
              Course progress: {{ completedCount }} of {{ totalCount }} lessons ({{ completionPercentage }}%)
            </p>
          </div>

          <nav class="lesson-nav d-flex ga-2 mb-10" aria-label="Lesson navigation">
            <v-btn
              v-if="lesson.navigation.previous"
              variant="outlined"
              prepend-icon="mdi-arrow-left"
              class="lesson-nav-btn"
              @click="goTo(lesson.navigation.previous.slug)"
            >
              <span class="lesson-nav-label">{{ lesson.navigation.previous.title }}</span>
            </v-btn>
            <v-spacer />
            <v-btn
              v-if="lesson.navigation.next"
              color="primary"
              append-icon="mdi-arrow-right"
              class="lesson-nav-btn"
              @click="goTo(lesson.navigation.next.slug)"
            >
              <span class="lesson-nav-label">{{ lesson.navigation.next.title }}</span>
            </v-btn>
          </nav>
        </article>
      </v-col>

      <v-col cols="12" md="4" lg="3" class="d-none d-md-block">
        <aside class="toc-rail" aria-label="Lesson contents">
          <v-card variant="outlined" class="toc-card">
            <v-card-item>
              <v-card-title class="text-subtitle-1">On this page</v-card-title>
            </v-card-item>
            <v-card-text>
              <nav aria-label="Lesson sections">
                <ol class="toc-list">
                  <li v-for="item in tocItems" :key="item.id">
                    <a :href="`#${item.id}`" class="toc-link">{{ item.label }}</a>
                  </li>
                </ol>
              </nav>
            </v-card-text>
          </v-card>
        </aside>
      </v-col>
    </v-row>
  </v-container>
</template>

<style scoped>
.lesson-wrap {
  max-width: 1200px;
}

.lesson-section {
  scroll-margin-top: 84px;
}

.toc-rail {
  position: sticky;
  top: 84px;
}

.toc-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.toc-link {
  display: block;
  padding: 4px 8px;
  border-radius: 8px;
  font-size: 0.875rem;
  color: inherit;
  text-decoration: none;
  border-inline-start: 2px solid transparent;
}

.toc-link:hover {
  background: rgba(79, 70, 229, 0.08);
  border-inline-start-color: #4F46E5;
}

.solution-panel {
  overflow: hidden;
}

.lesson-nav-btn {
  min-width: 0;
  max-width: 48%;
}

.lesson-nav-label {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

@media (max-width: 767px) {
  .lesson-nav {
    flex-direction: column;
  }

  .lesson-nav-btn {
    max-width: 100%;
  }
}
</style>
