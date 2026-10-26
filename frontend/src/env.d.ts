/// <reference types="vite/client" />
declare module '*.vue' {
  import type { DefineComponent } from 'vue'
  const component: DefineComponent<object, object, unknown>
  export default component
}

interface ImportMetaEnv {
  /** API base URL, e.g. http://localhost:8087/api */
  readonly VITE_API_URL: string
  /** Sentry DSN for the SPA; empty = Sentry off. */
  readonly VITE_SENTRY_DSN?: string
  readonly VITE_SENTRY_TRACES_SAMPLE_RATE?: string
  /** Privacy policy (/privacidade): controller's name, CNPJ/CPF and the privacy contact e-mail. */
  readonly VITE_PRIVACY_CONTROLLER?: string
  readonly VITE_PRIVACY_DOCUMENT?: string
  readonly VITE_PRIVACY_EMAIL?: string
  /** "true" shows "Continuar com Google" (needs GOOGLE_CLIENT_ID/SECRET on the API). */
  readonly VITE_GOOGLE_LOGIN?: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
