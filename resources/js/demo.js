import { createApp } from 'vue'
import SettingsPage from './pages/SettingsPage.vue'
import axios from 'axios'

// Simple axios mock for demo (uses localStorage)
const DEMO_KEY = 'demo_settings'

axios.get = async (url) => {
  if (url.includes('/api/public/settings')) {
    const payload = JSON.parse(localStorage.getItem(DEMO_KEY) || '{}')
    return { data: payload }
  }
  return { data: {} }
}

axios.post = async (url, body) => {
  if (url.includes('/api/public/settings')) {
    localStorage.setItem(DEMO_KEY, JSON.stringify(body))
    return { data: { ok: true } }
  }
  if (url.includes('/api/public/import')) {
    return { data: { job_id: 'demo_' + Date.now() } }
  }
  return { data: {} }
}

const app = createApp(SettingsPage)
app.mount('#demo-app')
