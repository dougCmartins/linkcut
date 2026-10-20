import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import { router } from './router'
import { installAuthGuard } from './Domain/Identity/auth.guard'
import { installClientGuard } from './Domain/Client/client.guard'
import { i18n } from './i18n'
import { initSentry } from './observability/sentry'
import './styles/core.css'

const app = createApp(App)
initSentry(app, router)
app.use(createPinia())
installAuthGuard(router)
installClientGuard(router)
app.use(router).use(i18n)

app.mount('#app')
