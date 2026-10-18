import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import { router } from './router'
import { i18n } from './i18n'
import { useSettingsStore } from './Settings/settings.store'
import './styles/core.css'

const app = createApp(App).use(createPinia()).use(router).use(i18n)

// Uploaded images live in IndexedDB; load them in the background.
void useSettingsStore().loadStoredImages()

app.mount('#app')
