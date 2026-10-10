<script setup lang="ts">
import { reactive, watch } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import FormField from '@/components/forms/FormField.vue'
import { useI18n } from '@/composables/useI18n'
import { useAppStore } from '@/stores/app'
import type { Activity } from '@/types/domain'

const props = defineProps<{ activity: Activity }>()
const emit = defineEmits<{ save: [activity: Activity]; cancel: [] }>()
const app = useAppStore()
const { t } = useI18n()
const draft = reactive({ ...props.activity })
const errors = reactive<Record<string, string>>({})

watch(() => props.activity, (activity) => Object.assign(draft, activity))

function save() {
  for (const key of Object.keys(errors)) delete errors[key]
  if (!draft.name.trim()) errors.name = t('validation.required')
  if (!Number.isFinite(draft.minutes) || draft.minutes < 1 || draft.minutes > 600) errors.minutes = t('validation.minutes')
  if (!Number.isFinite(draft.calories) || draft.calories < 0 || draft.calories > 5000) errors.calories = t('validation.calories')
  if (!draft.date) errors.date = t('validation.required')
  if (Object.keys(errors).length) return
  emit('save', { ...draft, name: draft.name.trim(), nameEn: undefined })
}
</script>

<template>
  <form class="grid gap-4" @submit.prevent="save">
    <FormField id="activity-name" :label="t('activity.name')" :error="errors.name">
      <Input id="activity-name" v-model="draft.name" class="h-11" :aria-invalid="!!errors.name" />
    </FormField>
    <FormField id="activity-date" :label="t('meal.date')" :error="errors.date">
      <Input id="activity-date" v-model="draft.date" type="date" class="h-11" :min="app.earliest" :max="app.today" :aria-invalid="!!errors.date" />
    </FormField>
    <FormField id="activity-minutes" :label="t('activity.duration')" :error="errors.minutes">
      <Input id="activity-minutes" v-model.number="draft.minutes" type="number" min="1" class="h-11" :aria-invalid="!!errors.minutes" />
    </FormField>
    <FormField id="activity-intensity" :label="t('activity.intensity')">
      <Select v-model="draft.intensity">
        <SelectTrigger id="activity-intensity" class="h-11 w-full">
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="low">{{ t('activity.low') }}</SelectItem>
          <SelectItem value="moderate">{{ t('activity.moderate') }}</SelectItem>
          <SelectItem value="high">{{ t('activity.high') }}</SelectItem>
        </SelectContent>
      </Select>
    </FormField>
    <FormField id="activity-kcal" :label="t('activity.calories')" :error="errors.calories">
      <Input id="activity-kcal" v-model.number="draft.calories" type="number" min="0" class="h-11" :aria-invalid="!!errors.calories" />
      <p class="text-sm text-muted-foreground">{{ t('activity.caloriesHelp') }}</p>
    </FormField>
    <FormField id="activity-notes" :label="t('activity.notes')" :optional="t('common.optional')">
      <Textarea id="activity-notes" v-model="draft.notes" />
    </FormField>
    <div class="grid gap-2 sm:grid-cols-2">
      <Button type="submit" class="h-11 hover:bg-primary-hover">{{ t('common.save') }}</Button>
      <Button type="button" variant="outline" class="h-11" @click="emit('cancel')">{{ t('common.cancel') }}</Button>
    </div>
  </form>
</template>
