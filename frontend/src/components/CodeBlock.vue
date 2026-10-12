<script setup>
import { computed, ref } from 'vue'
import hljs from '../utils/highlight'

const props = defineProps({
  code: { type: String, required: true },
  language: { type: String, default: 'text' },
})

const copied = ref(false)

const highlighted = computed(() => {
  if (props.language && hljs.getLanguage(props.language)) {
    try {
      return hljs.highlight(props.code, { language: props.language }).value
    } catch {
      // fall through to escaped plain text below
    }
  }

  const div = document.createElement('div')
  div.textContent = props.code
  return div.innerHTML
})

async function copyCode() {
  try {
    await navigator.clipboard.writeText(props.code)
    copied.value = true
    setTimeout(() => (copied.value = false), 1800)
  } catch {
    // Clipboard API unavailable — nothing we can do without an insecure fallback.
  }
}
</script>

<template>
  <div class="code-block">
    <div class="code-block__toolbar">
      <span class="code-block__lang">{{ language }}</span>
      <v-btn
        size="small"
        variant="text"
        :prepend-icon="copied ? 'mdi-check' : 'mdi-content-copy'"
        @click="copyCode"
      >
        {{ copied ? 'Copied' : 'Copy' }}
      </v-btn>
    </div>
    <pre class="code-block__pre"><code v-html="highlighted"></code></pre>
  </div>
</template>

<style scoped>
.code-block {
  border-radius: 8px;
  overflow: hidden;
  background: #0d1117;
  margin: 12px 0;
}

.code-block__toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 4px 8px 4px 16px;
  background: #161b22;
  color: #8b949e;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.code-block__pre {
  margin: 0;
  padding: 16px;
  overflow-x: auto;
  font-family: 'Fira Code', Consolas, Monaco, monospace;
  font-size: 0.875rem;
  line-height: 1.6;
}
</style>
