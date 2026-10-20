<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import ToggleSwitch from '@/components/ui/ToggleSwitch.vue'
import { PushSupport, usePushStore } from './push.store'

/** On/off for push notifications on this device, with the reason when it can't be turned on. */
const { t } = useI18n()
const push = usePushStore()

const isOn = computed({
  get: () => push.isSubscribed,
  set: (on: boolean) => void (on ? push.enable() : push.disable()),
})

/** Why the switch is not shown (unsupported, iPhone outside the home screen app, blocked by the browser). */
const blocker = computed(() => {
  if (push.support === PushSupport.Unsupported) return t('push.unsupported')
  if (push.support === PushSupport.NeedsInstall) return t('push.needsInstall')
  if (push.isBlocked) return t('push.blocked')
  return ''
})

onMounted(() => push.refresh())
</script>

<template>
  <div class="grid gap-3">
    <p v-if="blocker" class="rounded-card bg-raised p-4 text-sm text-muted ring-1 ring-line" role="status">{{ blocker }}</p>
    <ToggleSwitch
      v-else-if="push.support === PushSupport.Ready"
      v-model="isOn"
      :label="t('push.switchLabel')"
      :description="push.isBusy ? t('push.working') : t('push.switchDescription')"
    />
    <p v-if="push.hasFailed" class="text-sm text-danger" role="alert">{{ t('push.failed') }}</p>
  </div>
</template>
