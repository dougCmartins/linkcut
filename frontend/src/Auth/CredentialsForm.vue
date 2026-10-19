<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import TextField from '@/components/ui/TextField.vue'
import { credentialsErrors } from './auth.schemas'
import type { Credentials } from './auth.model'

defineProps<{ isBusy: boolean; demoAccount: Credentials | null }>()
const emit = defineEmits<{ submit: [credentials: Credentials] }>()
const { t } = useI18n()

const form = reactive({ email: '', password: '' })
const isPasswordVisible = ref(false)
const wasSubmitted = ref(false)

// Field errors only appear after the first submit, then update live.
const errors = computed(() => (wasSubmitted.value ? credentialsErrors(form) : {}))
const errorFor = (field: keyof typeof form) => {
  const key = errors.value[field]
  return key ? t(`auth.errors.${key}`) : undefined
}

function submit(): void {
  wasSubmitted.value = true
  if (Object.keys(credentialsErrors(form)).length > 0) return
  emit('submit', { email: form.email.trim(), password: form.password })
}

function useDemoAccount(account: Credentials): void {
  form.email = account.email
  form.password = account.password
}
</script>

<template>
  <form class="grid gap-4" novalidate @submit.prevent="submit">
    <TextField
      v-model="form.email"
      type="email"
      inputmode="email"
      autocomplete="username"
      :label="t('auth.email')"
      :placeholder="t('auth.emailPlaceholder')"
      :error="errorFor('email')"
    />
    <div>
      <TextField
        v-model="form.password"
        :type="isPasswordVisible ? 'text' : 'password'"
        autocomplete="current-password"
        :label="t('auth.password')"
        :error="errorFor('password')"
      />
      <button
        type="button"
        class="mt-2 pl-2 text-xs font-semibold text-muted hover:text-ink"
        :aria-pressed="isPasswordVisible"
        @click="isPasswordVisible = !isPasswordVisible"
      >
        {{ isPasswordVisible ? t('auth.hidePassword') : t('auth.showPassword') }}
      </button>
    </div>

    <BaseButton type="submit" variant="primary" size="lg" elevated block :disabled="isBusy">
      {{ isBusy ? t('auth.checking') : t('auth.continue') }}
    </BaseButton>

    <p v-if="demoAccount" class="rounded-card bg-raised px-4 py-3 text-xs leading-relaxed text-muted ring-1 ring-line">
      {{ t('auth.demoHint', { email: demoAccount.email, password: demoAccount.password }) }}
      <button type="button" class="ml-1 font-bold text-accent underline" @click="useDemoAccount(demoAccount)">
        {{ t('auth.useDemo') }}
      </button>
    </p>
  </form>
</template>
