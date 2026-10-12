<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import apiClient from '../api/client'

const tasks = ref([])
const total = ref(0)
const categories = ref([])
const stats = ref({ TODO: 0, IN_PROGRESS: 0, DONE: 0 })
const loading = ref(false)
const loadError = ref(null)
const actionError = ref(null)

const filters = reactive({
  status: null,
  priority: null,
  search: '',
  page: 1,
  perPage: 10,
})

const dialogOpen = ref(false)
const saving = ref(false)
const formError = ref(null)
const editingId = ref(null)
const form = reactive({
  title: '',
  description: '',
  status: 'TODO',
  priority: 'MEDIUM',
  dueDate: '',
  category: '',
})

const STATUS_OPTIONS = ['TODO', 'IN_PROGRESS', 'DONE']
const PRIORITY_OPTIONS = ['LOW', 'MEDIUM', 'HIGH']

const statusColor = { TODO: 'grey', IN_PROGRESS: 'info', DONE: 'success' }
const priorityColor = { LOW: 'grey', MEDIUM: 'warning', HIGH: 'error' }

async function loadTasks() {
  loading.value = true
  loadError.value = null
  try {
    const { data } = await apiClient.get('/tasks', {
      params: {
        status: filters.status || undefined,
        priority: filters.priority || undefined,
        search: filters.search || undefined,
        page: filters.page,
        perPage: filters.perPage,
      },
    })
    tasks.value = data.items
    total.value = data.total
  } catch (error) {
    loadError.value = error.response?.data?.error?.message || 'Could not load tasks. Please try again.'
    tasks.value = []
    total.value = 0
  } finally {
    loading.value = false
  }
}

async function loadCategories() {
  try {
    const { data } = await apiClient.get('/categories')
    categories.value = data.items
  } catch {
    // Non-critical: the category picker just falls back to free text entry.
  }
}

async function loadStats() {
  try {
    const { data } = await apiClient.get('/stats')
    stats.value = data.byStatus
  } catch {
    // Non-critical: the stat tiles simply keep their last known values.
  }
}

function openCreateDialog() {
  editingId.value = null
  Object.assign(form, { title: '', description: '', status: 'TODO', priority: 'MEDIUM', dueDate: '', category: '' })
  formError.value = null
  dialogOpen.value = true
}

function openEditDialog(task) {
  editingId.value = task.id
  Object.assign(form, {
    title: task.title,
    description: task.description || '',
    status: task.status,
    priority: task.priority,
    dueDate: task.dueDate || '',
    category: task.category || '',
  })
  formError.value = null
  dialogOpen.value = true
}

async function saveTask() {
  saving.value = true
  formError.value = null
  const payload = { ...form, dueDate: form.dueDate || null, category: form.category || null }

  try {
    if (editingId.value) {
      await apiClient.put(`/tasks/${editingId.value}`, payload)
    } else {
      await apiClient.post('/tasks', payload)
    }
    dialogOpen.value = false
    await Promise.all([loadTasks(), loadCategories(), loadStats()])
  } catch (error) {
    const details = error.response?.data?.error?.details
    formError.value = details ? details.map((d) => d.message).join(' ') : (error.response?.data?.error?.message || 'Could not save the task.')
  } finally {
    saving.value = false
  }
}

async function deleteTask(task) {
  if (!window.confirm(`Delete "${task.title}"? This cannot be undone.`)) {
    return
  }

  actionError.value = null
  try {
    await apiClient.delete(`/tasks/${task.id}`)
    await Promise.all([loadTasks(), loadStats()])
  } catch (error) {
    actionError.value = error.response?.data?.error?.message || 'Could not delete the task.'
  }
}

async function quickSetStatus(task, status) {
  actionError.value = null
  try {
    await apiClient.put(`/tasks/${task.id}`, {
      title: task.title,
      description: task.description,
      status,
      priority: task.priority,
      dueDate: task.dueDate,
      category: task.category,
    })
    await Promise.all([loadTasks(), loadStats()])
  } catch (error) {
    actionError.value = error.response?.data?.error?.message || 'Could not update the task status.'
  }
}

watch(() => [filters.status, filters.priority, filters.search], () => {
  filters.page = 1
  loadTasks()
})

watch(() => filters.page, loadTasks)

onMounted(() => {
  loadTasks()
  loadCategories()
  loadStats()
})
</script>

<template>
  <v-container class="py-6" style="max-width: 1000px">
    <div class="d-flex align-center justify-space-between mb-4 flex-wrap ga-2">
      <h1 class="text-h4">Task Manager</h1>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">New Task</v-btn>
    </div>

    <v-row class="mb-2">
      <v-col cols="12" sm="4">
        <v-card variant="tonal" color="grey" class="pa-3 text-center">
          <p class="text-caption">TODO</p>
          <p class="text-h5">{{ stats.TODO }}</p>
        </v-card>
      </v-col>
      <v-col cols="12" sm="4">
        <v-card variant="tonal" color="info" class="pa-3 text-center">
          <p class="text-caption">In Progress</p>
          <p class="text-h5">{{ stats.IN_PROGRESS }}</p>
        </v-card>
      </v-col>
      <v-col cols="12" sm="4">
        <v-card variant="tonal" color="success" class="pa-3 text-center">
          <p class="text-caption">Done</p>
          <p class="text-h5">{{ stats.DONE }}</p>
        </v-card>
      </v-col>
    </v-row>

    <v-card variant="outlined" class="mb-4 pa-3">
      <v-row dense>
        <v-col cols="12" sm="4">
          <v-text-field v-model="filters.search" label="Search title/description" aria-label="Search tasks by title or description" prepend-inner-icon="mdi-magnify" density="compact" clearable hide-details />
        </v-col>
        <v-col cols="6" sm="4">
          <v-select v-model="filters.status" :items="STATUS_OPTIONS" label="Status" density="compact" clearable hide-details />
        </v-col>
        <v-col cols="6" sm="4">
          <v-select v-model="filters.priority" :items="PRIORITY_OPTIONS" label="Priority" density="compact" clearable hide-details />
        </v-col>
      </v-row>
    </v-card>

    <v-alert v-if="actionError" type="error" variant="tonal" class="mb-4" role="alert" aria-live="assertive" closable :text="actionError" @click:close="actionError = null" />

    <div v-if="loading" class="text-center py-8" role="status" aria-live="polite">
      <v-progress-circular indeterminate color="primary" />
      <span class="visually-hidden">Loading tasks&hellip;</span>
    </div>

    <v-alert v-else-if="loadError" type="error" variant="tonal" role="alert" aria-live="assertive" :text="loadError">
      <template #append>
        <v-btn size="small" variant="outlined" @click="loadTasks">Retry</v-btn>
      </template>
    </v-alert>

    <v-alert v-else-if="0 === tasks.length" type="info" variant="tonal" text="No tasks match your filters yet. Create one to get started." />

    <v-card v-for="task in tasks" :key="task.id" variant="outlined" class="mb-3">
      <v-card-text>
        <div class="d-flex justify-space-between align-start ga-2">
          <div>
            <p class="text-body-1 font-weight-medium mb-1">{{ task.title }}</p>
            <p v-if="task.description" class="text-body-2 text-medium-emphasis mb-2">{{ task.description }}</p>
            <div class="d-flex ga-2 flex-wrap">
              <v-chip size="small" :color="statusColor[task.status]" variant="flat">{{ task.status }}</v-chip>
              <v-chip size="small" :color="priorityColor[task.priority]" variant="tonal">{{ task.priority }}</v-chip>
              <v-chip v-if="task.category" size="small" variant="outlined">{{ task.category }}</v-chip>
              <v-chip v-if="task.dueDate" size="small" variant="text" prepend-icon="mdi-calendar">{{ task.dueDate }}</v-chip>
            </div>
          </div>
          <div class="d-flex ga-1">
            <v-btn icon="mdi-pencil" size="small" variant="text" :aria-label="`Edit ${task.title}`" @click="openEditDialog(task)" />
            <v-btn icon="mdi-delete" size="small" variant="text" color="error" :aria-label="`Delete ${task.title}`" @click="deleteTask(task)" />
          </div>
        </div>
        <div class="mt-3 d-flex ga-2" role="group" :aria-label="`Change status for ${task.title}`">
          <v-btn
            v-for="status in STATUS_OPTIONS"
            :key="status"
            size="x-small"
            :variant="task.status === status ? 'flat' : 'outlined'"
            :color="statusColor[status]"
            :aria-pressed="task.status === status ? 'true' : 'false'"
            @click="quickSetStatus(task, status)"
          >
            {{ status }}
          </v-btn>
        </div>
      </v-card-text>
    </v-card>

    <div class="d-flex justify-center mt-4" v-if="total > filters.perPage">
      <v-pagination v-model="filters.page" :length="Math.ceil(total / filters.perPage)" :total-visible="7" />
    </div>

    <v-dialog v-model="dialogOpen" max-width="520">
      <v-card>
        <v-card-title>{{ editingId ? 'Edit Task' : 'New Task' }}</v-card-title>
        <v-card-text>
          <v-form @submit.prevent="saveTask">
            <v-text-field v-model="form.title" label="Title" required />
            <v-textarea v-model="form.description" label="Description" rows="2" />
            <v-row dense>
              <v-col cols="6">
                <v-select v-model="form.status" :items="STATUS_OPTIONS" label="Status" />
              </v-col>
              <v-col cols="6">
                <v-select v-model="form.priority" :items="PRIORITY_OPTIONS" label="Priority" />
              </v-col>
            </v-row>
            <v-text-field v-model="form.dueDate" label="Due date" type="date" />
            <v-combobox v-model="form.category" :items="categories.map((c) => c.name)" label="Category" clearable />
            <v-alert v-if="formError" type="error" variant="tonal" density="compact" class="mb-2" :text="formError" />
            <v-card-actions class="px-0">
              <v-spacer />
              <v-btn variant="text" @click="dialogOpen = false">Cancel</v-btn>
              <v-btn color="primary" type="submit" :loading="saving">Save</v-btn>
            </v-card-actions>
          </v-form>
        </v-card-text>
      </v-card>
    </v-dialog>
  </v-container>
</template>
