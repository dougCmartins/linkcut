<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import ToggleSwitch from '@/components/ui/ToggleSwitch.vue'
import { useApiErrors } from '@/composables/useApiErrors'
import { SaveResult } from '@/Domain/Settings/settings.model'
import { useSettingsStore } from '@/Domain/Settings/settings.store'
import { formatDayMonth, fromIsoDate } from '@/utils/date'
import StarRating from './StarRating.vue'
import { featureReviews, fetchReviews, hideReview, type BarberReview } from './review.api'

const MAX_FEATURED = 3

const { t } = useI18n()
const settings = useSettingsStore()
const { messageKey } = useApiErrors()

const reviews = ref<BarberReview[]>([])
const featuredIds = ref<number[]>([])
const loading = ref(true)
const error = ref('')
const hidingId = ref<number | null>(null)

const featuredCount = computed(() => featuredIds.value.length)

function syncFeatured(): void {
  featuredIds.value = reviews.value
    .filter((review) => review.featuredPosition !== null && review.comment)
    .sort((a, b) => (a.featuredPosition ?? 0) - (b.featuredPosition ?? 0))
    .map((review) => review.id)
}

function positionOf(id: number): number {
  return featuredIds.value.indexOf(id)
}

async function load(): Promise<void> {
  loading.value = true
  error.value = ''
  try {
    await settings.ensureLoaded()
    reviews.value = await fetchReviews()
    syncFeatured()
  } catch (caught) {
    error.value = t(`errors.api.${messageKey(caught)}`)
  } finally {
    loading.value = false
  }
}

async function setFlag(flag: 'showRating' | 'showReviews', value: boolean): Promise<void> {
  if (!settings.isLoaded) return
  const previous = settings.drafts.portfolio[flag]
  settings.drafts.portfolio[flag] = value
  try {
    if ((await settings.save('portfolio')) !== SaveResult.Saved) settings.drafts.portfolio[flag] = previous
  } catch (caught) {
    settings.drafts.portfolio[flag] = previous
    error.value = t(`errors.api.${messageKey(caught)}`)
  }
}

async function saveFeatured(next: number[]): Promise<void> {
  const previous = featuredIds.value
  featuredIds.value = next
  try {
    await featureReviews(next)
  } catch (caught) {
    featuredIds.value = previous
    error.value = t(`errors.api.${messageKey(caught)}`)
  }
}

function toggleFeatured(review: BarberReview): void {
  if (!review.comment || review.hiddenAt) return
  const index = positionOf(review.id)
  if (index >= 0) {
    void saveFeatured(featuredIds.value.filter((id) => id !== review.id))
    return
  }
  if (featuredCount.value >= MAX_FEATURED) return
  void saveFeatured([...featuredIds.value, review.id])
}

function move(id: number, direction: -1 | 1): void {
  const index = positionOf(id)
  const nextIndex = index + direction
  if (index < 0 || nextIndex < 0 || nextIndex >= featuredIds.value.length) return
  const next = [...featuredIds.value]
  const moved = next.splice(index, 1)[0]
  if (moved === undefined) return
  next.splice(nextIndex, 0, moved)
  void saveFeatured(next)
}

async function hide(review: BarberReview): Promise<void> {
  hidingId.value = review.id
  error.value = ''
  try {
    const updated = await hideReview(review.id)
    reviews.value = reviews.value.map((item) => (item.id === updated.id ? updated : item))
    featuredIds.value = featuredIds.value.filter((id) => id !== review.id)
  } catch (caught) {
    error.value = t(`errors.api.${messageKey(caught)}`)
  } finally {
    hidingId.value = null
  }
}

onMounted(() => void load())
</script>

<template>
  <main class="mx-auto w-full max-w-md px-4 pt-6 pb-12">
    <header class="flex items-center gap-3">
      <RouterLink to="/painel" class="text-sm font-bold text-accent" :aria-label="t('reviews.back')">←</RouterLink>
      <h1 class="text-xl">{{ t('reviews.panelTitle') }}</h1>
    </header>

    <div v-if="settings.isLoaded" class="mt-6 flex flex-col gap-4 rounded-card bg-surface p-4 ring-1 ring-line shadow-soft">
      <ToggleSwitch
        :model-value="settings.drafts.portfolio.showRating"
        :label="t('reviews.showRating')"
        :description="t('reviews.showRatingHint')"
        @update:model-value="setFlag('showRating', $event)"
      />
      <ToggleSwitch
        :model-value="settings.drafts.portfolio.showReviews"
        :label="t('reviews.showReviews')"
        :description="t('reviews.showReviewsHint')"
        @update:model-value="setFlag('showReviews', $event)"
      />
    </div>

    <p v-if="loading" class="mt-6 text-sm text-muted" role="status">{{ t('reviews.loading') }}</p>
    <p v-else-if="reviews.length === 0" class="mt-6 text-sm text-muted">{{ t('reviews.empty') }}</p>
    <ul v-else class="mt-6 flex flex-col gap-3">
      <li v-for="review in reviews" :key="review.id" class="rounded-card bg-surface p-4 ring-1 ring-line shadow-soft">
        <div class="flex items-center gap-2">
          <p class="text-sm font-bold">{{ review.firstName }}</p>
          <StarRating :value="review.rating" size="xs" />
        </div>
        <p class="mt-2 text-[13px] leading-snug break-words" :class="review.comment ? 'text-ink' : 'text-muted'">
          {{ review.comment || t('reviews.noComment') }}
        </p>
        <p v-if="!review.comment && !review.hiddenAt" class="mt-1 text-xs text-muted">{{ t('reviews.offCarousel') }}</p>
        <p class="mt-1 text-xs text-muted">{{ formatDayMonth(fromIsoDate(review.date)) }}</p>
        <div class="mt-3 flex flex-wrap items-center gap-3">
          <button
            v-if="!review.hiddenAt"
            type="button"
            class="text-sm font-semibold text-muted hover:text-danger disabled:opacity-60"
            :disabled="hidingId === review.id"
            @click="hide(review)"
          >
            {{ hidingId === review.id ? t('reviews.hiding') : t('reviews.hide') }}
          </button>
          <span v-else class="text-sm font-semibold text-muted">{{ t('reviews.hidden') }}</span>
          <template v-if="review.comment && !review.hiddenAt">
            <button
              type="button"
              class="text-sm font-bold text-accent disabled:opacity-40"
              :disabled="positionOf(review.id) < 0 && featuredCount >= MAX_FEATURED"
              :aria-pressed="positionOf(review.id) >= 0"
              @click="toggleFeatured(review)"
            >
              {{ positionOf(review.id) >= 0 ? t('reviews.onPage') : t('reviews.showOnPage') }}
            </button>
            <button v-if="positionOf(review.id) > 0" type="button" class="text-xs font-semibold text-muted" @click="move(review.id, -1)">
              {{ t('reviews.moveUp') }}
            </button>
            <button
              v-if="positionOf(review.id) >= 0 && positionOf(review.id) < featuredIds.length - 1"
              type="button"
              class="text-xs font-semibold text-muted"
              @click="move(review.id, 1)"
            >
              {{ t('reviews.moveDown') }}
            </button>
          </template>
        </div>
      </li>
    </ul>
    <p v-if="error" class="mt-4 text-sm font-semibold text-danger" role="alert">{{ error }}</p>
  </main>
</template>
