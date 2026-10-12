<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import apiClient from '../api/client'

const props = defineProps({
  slug: { type: String, required: true },
  questions: { type: Array, required: true },
})

const authStore = useAuthStore()
const router = useRouter()

const answers = ref(props.questions.map(() => null))
const submitting = ref(false)
const result = ref(null)
const error = ref(null)

function selectOption(questionIndex, optionIndex) {
  if (result.value) {
    return
  }
  answers.value[questionIndex] = optionIndex
}

async function submit() {
  error.value = null

  if (answers.value.some((answer) => null === answer)) {
    error.value = 'Please answer every question before submitting.'
    return
  }

  submitting.value = true
  try {
    const { data } = await apiClient.post(`/quizzes/${props.slug}/attempt`, { answers: answers.value })
    result.value = data
  } catch (submitError) {
    error.value = submitError.response?.data?.error?.message || 'Could not submit the quiz. Please try again.'
  } finally {
    submitting.value = false
  }
}

function retry() {
  answers.value = props.questions.map(() => null)
  result.value = null
  error.value = null
}

function goToLogin() {
  router.push({ name: 'login', query: { redirect: router.currentRoute.value.fullPath } })
}

function resultClass(currentResult, qIndex, oIndex) {
  if (!currentResult) {
    return ''
  }
  const answer = currentResult.answers[qIndex]
  if (oIndex === answer.correct) {
    return 'quiz-option--correct'
  }
  if (oIndex === answer.selected && !answer.isCorrect) {
    return 'quiz-option--incorrect'
  }
  return ''
}
</script>

<template>
  <v-card variant="outlined" class="mt-2">
    <v-card-text v-if="!authStore.isAuthenticated">
      <v-alert type="info" variant="tonal" text="Log in to take this quiz and save your score.">
        <template #append>
          <v-btn size="small" color="primary" @click="goToLogin">Log in</v-btn>
        </template>
      </v-alert>
    </v-card-text>

    <v-card-text v-else>
      <div v-for="(question, qIndex) in questions" :key="qIndex" class="mb-6">
        <p :id="`quiz-q-${qIndex}`" class="font-weight-medium mb-2">{{ qIndex + 1 }}. {{ question.question }}</p>

        <v-list density="compact" role="radiogroup" :aria-labelledby="`quiz-q-${qIndex}`">
          <v-list-item
            v-for="(option, oIndex) in question.options"
            :key="oIndex"
            :active="answers[qIndex] === oIndex"
            :class="resultClass(result, qIndex, oIndex)"
            role="radio"
            :aria-checked="answers[qIndex] === oIndex ? 'true' : 'false'"
            rounded="lg"
            @click="selectOption(qIndex, oIndex)"
          >
            <template #prepend>
              <v-icon :icon="answers[qIndex] === oIndex ? 'mdi-radiobox-marked' : 'mdi-radiobox-blank'" aria-hidden="true" />
            </template>
            <v-list-item-title>{{ option }}</v-list-item-title>
          </v-list-item>
        </v-list>

        <v-alert
          v-if="result"
          class="mt-2"
          :type="result.answers[qIndex].isCorrect ? 'success' : 'error'"
          variant="tonal"
          density="compact"
          role="status"
          :text="result.answers[qIndex].explanation"
        />
      </div>

      <v-alert v-if="error" type="error" variant="tonal" class="mb-4" role="alert" aria-live="assertive" :text="error" />

      <v-alert v-if="result" type="success" variant="tonal" class="mb-4">
        Score: {{ result.score }} / {{ result.totalQuestions }} ({{ result.percentage }}%)
      </v-alert>

      <v-btn v-if="!result" color="primary" :loading="submitting" @click="submit">Submit Quiz</v-btn>
      <v-btn v-else variant="outlined" @click="retry">Try Again</v-btn>
    </v-card-text>
  </v-card>
</template>

<style scoped>
.quiz-option--correct {
  background: rgba(var(--v-theme-success), 0.15);
}

.quiz-option--incorrect {
  background: rgba(var(--v-theme-error), 0.15);
}
</style>
