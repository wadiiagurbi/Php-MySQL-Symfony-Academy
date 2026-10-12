import MarkdownIt from 'markdown-it'
import hljs from './highlight'

const md = new MarkdownIt({
  html: false,
  linkify: true,
  typographer: true,
  highlight(code, lang) {
    if (lang && hljs.getLanguage(lang)) {
      try {
        return hljs.highlight(code, { language: lang }).value
      } catch {
        // fall through to unhighlighted output below
      }
    }

    return md.utils.escapeHtml(code)
  },
})

export function renderMarkdown(source) {
  return md.render(source || '')
}
