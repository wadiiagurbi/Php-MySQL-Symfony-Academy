<script setup>
import { computed } from 'vue'

const props = defineProps({
  difficulty: { type: String, required: true },
  size: { type: String, default: 'default' },
})

const color = computed(() => ({
  beginner: 'success',
  intermediate: 'warning',
  advanced: 'error',
}[props.difficulty] || 'grey'))
</script>

<template>
  <v-chip :color="color" :size="size" variant="flat" label>
    {{ difficulty }}
  </v-chip>
</template>
