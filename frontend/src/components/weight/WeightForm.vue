<script setup lang="ts">
import { reactive, watch } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import FormField from '@/components/forms/FormField.vue'
import { useI18n } from '@/composables/useI18n'
import { useAppStore } from '@/stores/app'
import type { WeightPoint } from '@/types/domain'

const props = defineProps<{ point: WeightPoint }>()
const emit = defineEmits<{ save: [point: WeightPoint]; cancel: [] }>()
const app = useAppStore()
const { t } = useI18n()
const draft = reactive({ id: props.point.id, date: props.point.date, kg: String(props.point.kg), notes: props.point.notes })
const errors = reactive<Record<string, string>>({})

watch(() => props.point, (point) => {
  draft.id = point.id
  draft.date = point.date
  draft.kg = String(point.kg)
  draft.notes = point.notes
})

function save() {
  for (const key of Object.keys(errors)) delete errors[key]
  const kg = Number(draft.kg.replace(',', '.'))
  if (!Number.isFinite(kg) || kg < 30 || kg > 300) errors.kg = t('onboarding.invalidWeight')
  if (!draft.date) errors.date = t('validation.required')
  if (Object.keys(errors).length) return
  emit('save', { id: draft.id, date: draft.date, kg: Math.round(kg * 10) / 10, notes: draft.notes.trim() })
}
</script>

<template>
  <form class="grid gap-4" @submit.prevent="save">
    <FormField id="weight-kg" :label="t('weight.value')" :error="errors.kg">
      <Input id="weight-kg" v-model="draft.kg" inputmode="decimal" class="h-11" :aria-invalid="!!errors.kg" />
    </FormField>
    <FormField id="weight-date" :label="t('weight.date')" :error="errors.date">
      <Input id="weight-date" v-model="draft.date" type="date" class="h-11" :min="app.earliest" :max="app.today" :aria-invalid="!!errors.date" />
    </FormField>
    <FormField id="weight-notes" :label="t('weight.notes')" :optional="t('common.optional')">
      <Textarea id="weight-notes" v-model="draft.notes" />
    </FormField>
    <div class="grid gap-2 sm:grid-cols-2">
      <Button type="submit" class="h-11 hover:bg-primary-hover">{{ t('common.save') }}</Button>
      <Button type="button" variant="outline" class="h-11" @click="emit('cancel')">{{ t('common.cancel') }}</Button>
    </div>
  </form>
</template>
