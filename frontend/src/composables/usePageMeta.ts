import { onBeforeUnmount, watchEffect, type MaybeRefOrGetter, toValue } from 'vue'

/** The title and description of index.html, put back when a page with its own leaves. */
const DEFAULT_TITLE = document.title
const DEFAULT_DESCRIPTION = descriptionTag()?.content ?? ''
const DESCRIPTION_MAX = 160

function descriptionTag(): HTMLMetaElement | null {
  return document.querySelector<HTMLMetaElement>('meta[name="description"]')
}

/**
 * Title and description of the current page (search results and the preview when the link is shared).
 * Empty values keep the defaults of index.html.
 */
export function usePageMeta(title: MaybeRefOrGetter<string>, description: MaybeRefOrGetter<string>): void {
  watchEffect(() => {
    const pageTitle = toValue(title).trim()
    const pageDescription = toValue(description).trim()
    document.title = pageTitle ? `${pageTitle} · linkcut` : DEFAULT_TITLE
    const tag = descriptionTag()
    if (tag) tag.content = (pageDescription || DEFAULT_DESCRIPTION).slice(0, DESCRIPTION_MAX)
  })

  onBeforeUnmount(() => {
    document.title = DEFAULT_TITLE
    const tag = descriptionTag()
    if (tag) tag.content = DEFAULT_DESCRIPTION
  })
}
