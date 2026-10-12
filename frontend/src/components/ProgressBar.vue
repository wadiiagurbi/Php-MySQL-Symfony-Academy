<script setup>
import { computed } from 'vue'

const props = defineProps({
  value: { type: Number, required: true },
  total: { type: Number, required: true },
  label: { type: String, default: 'Progress' },
})

const percentage = computed(() => {
  if (!props.total || props.total <= 0) {
    return 0
  }
  return Math.min(100, Math.max(0, Math.round((props.value / props.total) * 100)))
})
</script>

<template>
  <div class="progress-bar">
    <v-progress-linear
      :model-value="percentage"
      color="primary"
      height="6"
      rounded
      role="progressbar"
      :aria-valuemin="0"
      :aria-valuemax="100"
      :aria-valuenow="percentage"
      :aria-label="label"
      class="progress-bar__track"
    />
    <span class="progress-bar__label text-caption text-medium-emphasis" aria-hidden="true">
      {{ percentage }}%
    </span>
  </div>
</template>

<style scoped>
.progress-bar {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
}

.progress-bar__track {
  flex: 1 1 auto;
  min-width: 0;
}

.progress-bar__label {
  flex: 0 0 auto;
  font-variant-numeric: tabular-nums;
  min-width: 3ch;
  text-align: right;
}
</style>
