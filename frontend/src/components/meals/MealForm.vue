<script setup lang="ts">
import { reactive, watch } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import FormField from '@/components/forms/FormField.vue'
import { useI18n } from '@/composables/useI18n'
import { MEAL_TYPES } from '@/lib/meals'
import { uid } from '@/lib/id'
import { useAppStore } from '@/stores/app'
import type { FoodItem, FoodUnit, Meal, MealType } from '@/types/domain'

const props = defineProps<{ meal: Meal }>()
const emit = defineEmits<{ save: [meal: Meal]; cancel: [] }>()
const app = useAppStore()
const { t } = useI18n()

type DraftItem = {
  id: string
  name: string
  quantity: string
  unit: FoodUnit
  calories: string
  protein: string
  carbs: string
  fat: string
  foodId: number | null
  source: 'usda' | 'manual'
}

const draft = reactive({
  id: '',
  date: app.selectedDate,
  type: 'breakfast' as MealType,
  time: '12:00',
  items: [] as DraftItem[],
})
const errors = reactive<Record<string, string>>({})

function blankItem(): DraftItem {
  return { id: uid('food'), name: '', quantity: '', unit: 'g', calories: '', protein: '', carbs: '', fat: '', foodId: null, source: 'manual' }
}

function load(meal: Meal) {
  draft.id = meal.id
  draft.date = meal.date
  draft.type = meal.type
  draft.time = meal.time
  draft.items = meal.items.map((item) => ({
    id: item.id || uid('food'),
    name: item.name,
    quantity: String(item.quantity ?? ''),
    unit: item.unit,
    calories: item.calories ? String(item.calories) : '',
    protein: item.protein == null ? '' : String(item.protein),
    carbs: item.carbs == null ? '' : String(item.carbs),
    fat: item.fat == null ? '' : String(item.fat),
    foodId: item.foodId ?? null,
    source: item.source ?? 'manual',
  }))
  if (draft.items.length === 0) draft.items.push(blankItem())
  for (const key of Object.keys(errors)) delete errors[key]
}

watch(() => props.meal, load, { immediate: true })

function optionalNumber(value: string, key: string): number | null | undefined {
  if (!value.trim()) return null
  const parsed = Number(value.replace(',', '.'))
  if (!Number.isFinite(parsed) || parsed < 0) {
    errors[key] = t('validation.number')
    return undefined
  }
  return parsed
}

function save() {
  for (const key of Object.keys(errors)) delete errors[key]
  draft.items.forEach((item, index) => {
    if (!item.name.trim()) errors[`name-${index}`] = t('validation.required')
    const quantity = Number(item.quantity.replace(',', '.'))
    if (!Number.isFinite(quantity) || quantity <= 0) errors[`quantity-${index}`] = t('validation.quantity')
    const calories = Number(item.calories.replace(',', '.'))
    if (item.source !== 'usda' && (!Number.isFinite(calories) || calories < 0 || calories > 5000)) errors[`calories-${index}`] = t('validation.calories')
    optionalNumber(item.protein, `protein-${index}`)
    optionalNumber(item.carbs, `carbs-${index}`)
    optionalNumber(item.fat, `fat-${index}`)
  })
  if (!draft.date) errors.date = t('validation.required')
  if (!draft.time) errors.time = t('validation.required')
  if (Object.keys(errors).length > 0) return
  const items: FoodItem[] = draft.items.map((item) => ({
    id: item.id,
    name: item.name.trim(),
    quantity: Number(item.quantity.replace(',', '.')),
    unit: item.unit,
    calories: item.source === 'usda' ? 0 : Number(item.calories.replace(',', '.')),
    protein: item.protein.trim() ? Number(item.protein.replace(',', '.')) : null,
    carbs: item.carbs.trim() ? Number(item.carbs.replace(',', '.')) : null,
    fat: item.fat.trim() ? Number(item.fat.replace(',', '.')) : null,
    foodId: item.foodId,
    source: item.source,
  }))
  emit('save', { id: draft.id, date: draft.date, type: draft.type, time: draft.time, items })
}
</script>

<template>
  <form class="grid gap-4" @submit.prevent="save">
    <FormField id="meal-type" :label="t('meal.type')">
      <Select v-model="draft.type">
        <SelectTrigger id="meal-type" class="h-11 w-full">
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectItem v-for="type in MEAL_TYPES" :key="type" :value="type">{{ t(`meal.${type}`) }}</SelectItem>
        </SelectContent>
      </Select>
    </FormField>
    <div class="grid gap-4 sm:grid-cols-2">
      <FormField id="meal-date" :label="t('meal.date')" :error="errors.date">
        <Input id="meal-date" v-model="draft.date" type="date" class="h-11" :aria-invalid="!!errors.date" :min="app.earliest" :max="app.today" />
      </FormField>
      <FormField id="meal-time" :label="t('meal.time')" :error="errors.time">
        <Input id="meal-time" v-model="draft.time" type="time" class="h-11" :aria-invalid="!!errors.time" />
      </FormField>
    </div>
    <fieldset v-for="(item, index) in draft.items" :key="item.id" class="grid gap-3 rounded-xl border border-border p-3">
      <legend class="px-1 text-sm font-medium">{{ t('meal.itemLegend', { index: index + 1 }) }}</legend>
      <FormField :id="`food-name-${index}`" :label="t('meal.name')" :error="errors[`name-${index}`]">
        <Input :id="`food-name-${index}`" v-model="item.name" class="h-11" :aria-invalid="!!errors[`name-${index}`]" />
      </FormField>
      <div class="grid gap-3 sm:grid-cols-2">
        <FormField :id="`food-qty-${index}`" :label="t('meal.quantity')" :error="errors[`quantity-${index}`]">
          <Input :id="`food-qty-${index}`" v-model="item.quantity" inputmode="decimal" class="h-11" :aria-invalid="!!errors[`quantity-${index}`]" />
        </FormField>
        <FormField :id="`food-unit-${index}`" :label="t('meal.unit')">
          <Select v-model="item.unit">
            <SelectTrigger :id="`food-unit-${index}`" class="h-11 w-full">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="g">{{ t('unit.g') }}</SelectItem>
              <SelectItem value="ml">{{ t('unit.ml') }}</SelectItem>
              <SelectItem value="piece">{{ t('unit.piece') }}</SelectItem>
            </SelectContent>
          </Select>
        </FormField>
      </div>
      <FormField :id="`food-kcal-${index}`" :label="t('meal.calories')" :error="errors[`calories-${index}`]">
        <Input :id="`food-kcal-${index}`" v-model="item.calories" inputmode="decimal" class="h-11" :readonly="item.source === 'usda'" :aria-invalid="!!errors[`calories-${index}`]" />
      </FormField>
      <div class="grid gap-3 sm:grid-cols-3">
        <FormField :id="`food-p-${index}`" :label="t('meal.protein')" :optional="t('common.optional')" :error="errors[`protein-${index}`]">
          <Input :id="`food-p-${index}`" v-model="item.protein" inputmode="decimal" class="h-11" :aria-invalid="!!errors[`protein-${index}`]" />
        </FormField>
        <FormField :id="`food-c-${index}`" :label="t('meal.carbs')" :optional="t('common.optional')" :error="errors[`carbs-${index}`]">
          <Input :id="`food-c-${index}`" v-model="item.carbs" inputmode="decimal" class="h-11" :aria-invalid="!!errors[`carbs-${index}`]" />
        </FormField>
        <FormField :id="`food-f-${index}`" :label="t('meal.fat')" :optional="t('common.optional')" :error="errors[`fat-${index}`]">
          <Input :id="`food-f-${index}`" v-model="item.fat" inputmode="decimal" class="h-11" :aria-invalid="!!errors[`fat-${index}`]" />
        </FormField>
      </div>
      <Button v-if="draft.items.length > 1" type="button" variant="ghost" class="h-11" @click="draft.items.splice(index, 1)">
        {{ t('meal.removeItem') }}
      </Button>
    </fieldset>
    <Button type="button" variant="outline" class="h-11" @click="draft.items.push(blankItem())">
      {{ t('meal.addItem') }}
    </Button>
    <div class="grid gap-2 sm:grid-cols-2">
      <Button type="submit" class="h-11 hover:bg-primary-hover">{{ t('common.save') }}</Button>
      <Button type="button" variant="outline" class="h-11" @click="emit('cancel')">{{ t('common.cancel') }}</Button>
    </div>
  </form>
</template>
