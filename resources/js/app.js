import './bootstrap'
import { createApp } from 'vue'
import SettingsPage from './pages/SettingsPage.vue'

const app = createApp({})
app.component('settings-page', SettingsPage)
app.mount('#app')
