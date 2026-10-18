import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import { router } from './router'
import { i18n } from './i18n'
import { usePalette } from './composables/usePalette'
import './styles/main.css'

const { palette, applyPalette } = usePalette()
applyPalette(palette.value)

createApp(App).use(createPinia()).use(router).use(i18n).mount('#app')
