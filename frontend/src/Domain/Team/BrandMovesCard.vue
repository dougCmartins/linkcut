<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import TextField from '@/components/ui/TextField.vue'
import { useApiErrors } from '@/composables/useApiErrors'
import { blockingAppointments, endLoan, extendLoan, fetchBrandMoves, makeLoanPermanent, type BrandMove } from './staffMove.api'

/**
 * "Movimentos ativos" in "Minha equipe" (docs/decisoes/0003 P3): each loan with "Estender", "Encerrar agora" and
 * "Tornar definitivo". Shows nothing when the brand has no current move.
 */
const emit = defineEmits<{ changed: [] }>()
const { t } = useI18n()
const { messageKey } = useApiErrors()

const moves = ref<BrandMove[]>([])
const extendingId = ref<number | null>(null)
const newEndsOn = ref('')
const error = ref('')

async function load(): Promise<void> {
  try {
    moves.value = await fetchBrandMoves()
  } catch {
    moves.value = []
  }
}
onMounted(load)
defineExpose({ load })

async function run(action: () => Promise<void>): Promise<void> {
  error.value = ''
  try {
    await action()
    extendingId.value = null
    await load()
    emit('changed')
  } catch (caught) {
    const blocking = blockingAppointments(caught)
    error.value = t(`errors.api.${messageKey(caught)}`) + (blocking.length ? ` (${blocking.map((item) => `${item.date} ${item.startsAt}`).join(', ')})` : '')
  }
}

function startExtend(move: BrandMove): void {
  extendingId.value = move.id
  newEndsOn.value = move.endsOn ?? ''
}
</script>

<template>
  <section v-if="moves.length" class="mb-6 rounded-card bg-surface p-4 ring-1 ring-line" :aria-label="t('staffMove.moves.title')">
    <h2 class="text-lg">{{ t('staffMove.moves.title') }}</h2>
    <p v-if="error" class="mt-2 text-sm text-danger" role="alert">{{ error }}</p>
    <ul class="mt-3 grid gap-3">
      <li v-for="move in moves" :key="move.id" class="border-t border-line pt-3 first:border-0 first:pt-0">
        <p class="text-sm font-semibold">
          {{
            move.kind === 'loan'
              ? t('staffMove.moves.loan', { name: move.barberName, unit: move.toUnitName, date: move.endsOn })
              : t('staffMove.moves.transfer', { name: move.barberName, unit: move.toUnitName })
          }}
        </p>
        <div v-if="move.kind === 'loan'" class="mt-2 flex flex-wrap gap-2">
          <template v-if="extendingId === move.id">
            <TextField v-model="newEndsOn" class="flex-1" :label="t('staffMove.moves.extendTo')" type="date" />
            <BaseButton variant="primary" :disabled="!newEndsOn" @click="run(() => extendLoan(move.id, newEndsOn))">
              {{ t('staffMove.moves.saveExtend') }}
            </BaseButton>
          </template>
          <template v-else>
            <BaseButton variant="secondary" @click="startExtend(move)">{{ t('staffMove.moves.extend') }}</BaseButton>
            <BaseButton variant="secondary" @click="run(() => endLoan(move.id))">{{ t('staffMove.moves.endNow') }}</BaseButton>
            <BaseButton variant="ghost" @click="run(() => makeLoanPermanent(move.id))">{{ t('staffMove.moves.makePermanent') }}</BaseButton>
          </template>
        </div>
      </li>
    </ul>
  </section>
</template>
