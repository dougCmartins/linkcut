import { createApp, watch } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import { router } from './router'
import { installAuthGuard } from './Domain/Identity/auth.guard'
import { installClientGuard } from './Domain/Client/client.guard'
import { i18n } from './i18n'
import { initSentry, setPerformanceMonitoring } from './observability/sentry'
import { useConsentStore } from './Legal/consent.store'
import './styles/core.css'

const app = createApp(App)
initSentry(app, router)
app.use(createPinia())
// Sentry's performance monitoring follows the visitor's privacy choice (LGPD), also when it changes later.
const consent = useConsentStore()
watch(() => consent.allowsPerformance, setPerformanceMonitoring)
installAuthGuard(router)
installClientGuard(router)
app.use(router).use(i18n)

app.mount('#app')
