<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useApiErrors } from '@/composables/useApiErrors'
import ClientLayout from '@/Domain/Client/ClientLayout.vue'
import { useClientStore } from '@/Domain/Client/client.store'
import StarRating from './StarRating.vue'
import { submitReview } from './review.api'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const clientStore = useClientStore()
const { messageKey } = useApiErrors()

const rating = ref<number | null>(null)
const comment = ref('')
const sending = ref(false)
const error = ref('')
const appointmentId = computed(() => Number(route.params.appointment))

async function send(): Promise<void> {
  if (rating.value === null || sending.value) return
  sending.value = true
  error.value = ''
  try {
    await submitReview(appointmentId.value, rating.value, comment.value)
    if (clientStore.appointmentsLoaded) await clientStore.loadAppointments()
    await router.push('/conta')
  } catch (caught) {
    error.value = t(`errors.api.${messageKey(caught)}`)
    sending.value = false
  }
}
</script>

<template>
  <ClientLayout :title="t('reviews.title')">
    <RouterLink to="/conta" class="text-sm font-bold text-accent">← {{ t('reviews.back') }}</RouterLink>

    <form class="mt-6 flex flex-col gap-5" @submit.prevent="send">
      <div>
        <StarRating v-model="rating" :label="t('reviews.starsLabel')" />
      </div>

      <div>
        <label for="review-comment" class="block pl-2 text-xs font-semibold text-muted">{{ t('reviews.comment') }}</label>
        <textarea
          id="review-comment"
          v-model="comment"
          maxlength="500"
          rows="4"
          class="mt-1.5 w-full resize-none rounded-3xl bg-surface px-5 py-3 text-base text-ink ring-1 ring-line shadow-soft placeholder:text-muted/70 focus:ring-2 focus:ring-accent focus:outline-none"
        />
        <p class="mt-1.5 pl-2 text-xs text-muted tabular-nums">{{ t('reviews.commentCount', { count: comment.length }) }}</p>
      </div>

      <p class="text-sm leading-relaxed text-muted">{{ t('reviews.notice') }}</p>

      <button
        type="submit"
        class="h-12 rounded-full bg-accent text-base font-bold text-on-accent shadow-soft disabled:opacity-60"
        :disabled="rating === null || sending"
      >
        {{ sending ? t('reviews.sending') : t('reviews.submit') }}
      </button>
      <p v-if="error" class="text-sm font-semibold text-danger" role="alert">{{ error }}</p>
    </form>
  </ClientLayout>
</template>
