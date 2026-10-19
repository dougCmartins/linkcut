<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, useTemplateRef, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import OtpInput from '@/components/ui/OtpInput.vue'
import { CODE_LENGTH, codeSchema } from './auth.schemas'
import type { CodeDelivery } from './auth.model'
import type { LoginError } from './auth.store'

const props = defineProps<{ delivery: CodeDelivery; error: LoginError | null; isBusy: boolean }>()
const emit = defineEmits<{ submit: [code: string]; resend: []; changeChannel: [] }>()
const { t } = useI18n()

const code = ref('')
const otp = useTemplateRef<InstanceType<typeof OtpInput>>('otp')
const now = ref(Date.now())
let timer: ReturnType<typeof setInterval> | undefined

const isComplete = computed(() => codeSchema.safeParse(code.value).success)
const resendInSeconds = computed(() => Math.max(0, Math.ceil((props.delivery.resendAvailableAt - now.value) / 1000)))

// A wrong code clears the boxes so the barber can type again.
watch(
  () => props.error,
  (error) => {
    if (!error) return
    code.value = ''
    otp.value?.focus()
  },
)

// A new code (resend or other channel) also starts clean.
watch(
  () => props.delivery,
  () => (code.value = ''),
)

function submit(): void {
  if (isComplete.value && !props.isBusy) emit('submit', code.value)
}

onMounted(() => {
  otp.value?.focus()
  timer = setInterval(() => (now.value = Date.now()), 1000)
})
onBeforeUnmount(() => clearInterval(timer))
</script>

<template>
  <form class="grid gap-5" novalidate @submit.prevent="submit">
    <p class="text-sm leading-relaxed text-muted" role="status">
      {{ t(`auth.codeSentTo.${delivery.channel}`, { destination: delivery.sentTo }) }}
    </p>

    <OtpInput
      ref="otp"
      v-model="code"
      :length="CODE_LENGTH"
      :label="t('auth.codeLabel')"
      :disabled="isBusy"
      :invalid="Boolean(error)"
      @complete="submit"
    />

    <p v-if="delivery.devCode" class="rounded-card bg-accent/10 px-4 py-3 text-xs font-semibold ring-1 ring-accent/40">
      {{ t('auth.devCode', { code: delivery.devCode }) }}
    </p>

    <BaseButton type="submit" variant="primary" size="lg" elevated block :disabled="!isComplete || isBusy">
      {{ isBusy ? t('auth.checking') : t('auth.verify') }}
    </BaseButton>

    <div class="flex flex-wrap items-center justify-between gap-3 text-sm font-semibold">
      <button
        type="button"
        class="text-accent disabled:text-muted"
        :disabled="resendInSeconds > 0 || isBusy"
        @click="emit('resend')"
      >
        {{ resendInSeconds > 0 ? t('auth.resendIn', { seconds: resendInSeconds }) : t('auth.resend') }}
      </button>
      <button type="button" class="text-muted hover:text-ink" @click="emit('changeChannel')">
        {{ t('auth.changeChannel') }}
      </button>
    </div>
  </form>
</template>
