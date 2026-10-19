<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import BaseButton from '@/components/ui/BaseButton.vue'
import TextField from '@/components/ui/TextField.vue'
import type { FixedExpenseInput, OneOffExpenseInput } from './expenses.model'
import { EXPENSE_LIMITS, fieldErrors, fixedExpenseSchema, oneOffExpenseSchema } from './expenses.schemas'
import { parseMoneyInput } from '@/utils/currency'

/** Add form for a fixed (monthly) or a one-off expense. Validates with Zod before emitting. */
const props = defineProps<{ kind: 'fixed' | 'oneOff'; today: string }>()
const emit = defineEmits<{ submitFixed: [input: FixedExpenseInput]; submitOneOff: [input: OneOffExpenseInput]; cancel: [] }>()
const { t } = useI18n()

const fields = reactive({ name: '', amount: '', dueDay: '', date: props.today })
const errors = ref<Record<string, string>>({})
const errorFor = (field: string) => (errors.value[field] ? t(`expenses.errors.${errors.value[field]}`) : undefined)

function submit(): void {
  const amountInCents = parseMoneyInput(fields.amount) ?? Number.NaN
  if (props.kind === 'fixed') {
    const result = fixedExpenseSchema.safeParse({ name: fields.name, amountInCents, dueDay: Number(fields.dueDay || Number.NaN) })
    if (!result.success) {
      errors.value = fieldErrors(result.error)
      return
    }
    emit('submitFixed', result.data)
  } else {
    const result = oneOffExpenseSchema(props.today).safeParse({ description: fields.name, amountInCents, date: fields.date })
    if (!result.success) {
      // The form has one text field for both: show the description error on it.
      const { description, ...rest } = fieldErrors(result.error)
      errors.value = description ? { ...rest, name: description } : rest
      return
    }
    emit('submitOneOff', result.data)
  }
}
</script>

<template>
  <form class="grid gap-3 rounded-card bg-raised p-4 ring-1 ring-line" novalidate @submit.prevent="submit">
    <TextField
      v-model="fields.name"
      :label="kind === 'fixed' ? t('expenses.form.name') : t('expenses.form.description')"
      :placeholder="kind === 'fixed' ? t('expenses.form.namePlaceholder') : t('expenses.form.descriptionPlaceholder')"
      :maxlength="EXPENSE_LIMITS.nameMax"
      :error="errorFor('name')"
    />
    <div class="grid grid-cols-2 gap-3">
      <TextField
        v-model="fields.amount"
        inputmode="decimal"
        placeholder="0,00"
        :label="t('expenses.form.amount')"
        :error="errorFor('amountInCents')"
      />
      <TextField
        v-if="kind === 'fixed'"
        v-model="fields.dueDay"
        type="number"
        inputmode="numeric"
        :min="1"
        :max="31"
        :label="t('expenses.form.dueDay')"
        :error="errorFor('dueDay')"
      />
      <TextField
        v-else
        v-model="fields.date"
        type="date"
        :max="today"
        :label="t('expenses.form.date')"
        :error="errorFor('date')"
      />
    </div>
    <div class="mt-1 grid grid-cols-2 gap-3">
      <BaseButton variant="ghost" @click="emit('cancel')">{{ t('expenses.form.cancel') }}</BaseButton>
      <BaseButton type="submit" elevated>{{ t('expenses.form.save') }}</BaseButton>
    </div>
  </form>
</template>
