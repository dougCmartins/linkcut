<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'

/** Shown on the panel right after "Nova filial" (?filial=<slug>): the branch's own public link. */
const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const slug = computed(() => (typeof route.query.filial === 'string' ? route.query.filial : ''))
const link = computed(() => `${window.location.origin}/${slug.value}`)

function dismiss(): void {
  const { filial: _removed, ...query } = route.query
  void router.replace({ query })
}
</script>

<template>
  <div v-if="slug" class="mx-auto mt-4 flex w-full max-w-md items-start gap-3 rounded-card bg-success/10 px-4 py-3 text-sm" role="status">
    <p class="min-w-0 flex-1">
      {{ t('organization.newUnit.created') }}
      <a :href="link" class="block truncate font-semibold text-accent underline">{{ link }}</a>
    </p>
    <button type="button" class="font-semibold text-muted" :aria-label="t('organization.newUnit.dismiss')" @click="dismiss">×</button>
  </div>
</template>
