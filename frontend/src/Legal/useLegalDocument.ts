import { computed, onMounted, ref, type MaybeRefOrGetter, toValue } from 'vue'
import { useI18n } from 'vue-i18n'
import { i18n } from '@/i18n'
import { useQueryOverlay } from '@/composables/useQueryOverlay'

export type LegalDocument = 'privacy' | 'terms'

const LOADERS = {
  privacy: () => import('@/i18n/privacyPolicy.pt-BR'),
  terms: () => import('@/i18n/termsOfUse.pt-BR'),
} as const

/** The controller's name, document and contact e-mail come from the build, so the texts never change for them. */
const CONTACT = {
  controller: import.meta.env.VITE_PRIVACY_CONTROLLER || '[razão social]',
  document: import.meta.env.VITE_PRIVACY_DOCUMENT || '[CNPJ]',
  email: import.meta.env.VITE_PRIVACY_EMAIL || '[e-mail do encarregado]',
}

/**
 * The Privacy Policy or the Terms of Use, downloaded only when shown (page or overlay). Both are templates for
 * legal review.
 */
export function useLegalDocument(document: MaybeRefOrGetter<LegalDocument>) {
  const { t, tm, rt } = useI18n()
  const isLoaded = ref(false)

  onMounted(async () => {
    const messages = await LOADERS[toValue(document)]()
    i18n.global.mergeLocaleMessage('pt-BR', messages.default)
    isLoaded.value = true
  })

  const base = computed(() => `${toValue(document)}.page`)
  const title = computed(() => (isLoaded.value ? t(`${base.value}.title`) : ''))
  const updated = computed(() => (isLoaded.value ? t(`${base.value}.updated`) : ''))
  const intro = computed(() => (isLoaded.value ? t(`${base.value}.intro`) : ''))
  const sections = computed(() => {
    if (!isLoaded.value) return []
    const all = tm(`${base.value}.sections`) as Record<string, unknown>
    return Object.keys(all).map((key) => ({
      key,
      title: t(`${base.value}.sections.${key}.title`),
      items: (tm(`${base.value}.sections.${key}.items`) as unknown[]).map((item) => rt(item as Parameters<typeof rt>[0], CONTACT)),
    }))
  })

  return { isLoaded, title, updated, intro, sections }
}

const QUERY_VALUES: Record<string, LegalDocument> = { privacidade: 'privacy', termos: 'terms' }

/** "?documento=privacidade|termos": the legal text over the current screen (see LegalDialog). */
export function useLegalOverlay() {
  const overlay = useQueryOverlay('documento')
  const document = computed<LegalDocument | null>(() => (overlay.value.value ? (QUERY_VALUES[overlay.value.value] ?? null) : null))
  return {
    document,
    open: (which: LegalDocument) => overlay.open(which === 'terms' ? 'termos' : 'privacidade'),
    close: overlay.close,
  }
}
