<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { useI18n } from '@/composables/useI18n'
import { mealCalories, sumMacro } from '@/fixtures/demo'
import { formatKcal, formatNumber, localizedName } from '@/lib/format'
import { useAppStore } from '@/stores/app'
import type { Meal } from '@/types/domain'

defineProps<{ meal: Meal }>()
const emit = defineEmits<{ edit: []; remove: [] }>()
const app = useAppStore()
const { t } = useI18n()

function macro(value: number | null) {
  return value == null ? '—' : `${formatNumber(value, app.locale)} g`
}
</script>

<template>
  <div class="grid gap-4">
    <ul class="grid gap-3">
      <li v-for="item in meal.items" :key="item.id" class="grid gap-1 border-b border-border pb-3">
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="font-medium">{{ localizedName(item, app.locale) }}</p>
            <p class="text-sm text-muted-foreground">{{ item.quantity }} {{ t(`unit.${item.unit}`) }}</p>
          </div>
          <p class="font-medium text-energy-in">{{ formatKcal(item.calories, app.locale) }}</p>
        </div>
        <p class="text-xs text-muted-foreground">
          {{ t('dashboard.protein') }} {{ macro(item.protein) }} ·
          {{ t('dashboard.carbs') }} {{ macro(item.carbs) }} ·
          {{ t('dashboard.fat') }} {{ macro(item.fat) }}
        </p>
      </li>
    </ul>
    <p class="text-sm font-semibold">{{ t('meal.total') }}: {{ formatKcal(mealCalories(meal), app.locale) }}</p>
    <p class="text-sm text-muted-foreground">
      {{ t('dashboard.protein') }} {{ sumMacro(meal, 'protein').total }} g ·
      {{ t('dashboard.carbs') }} {{ sumMacro(meal, 'carbs').total }} g ·
      {{ t('dashboard.fat') }} {{ sumMacro(meal, 'fat').total }} g
    </p>
    <p v-if="!sumMacro(meal, 'protein').complete || !sumMacro(meal, 'carbs').complete || !sumMacro(meal, 'fat').complete" class="text-sm text-muted-foreground">
      {{ t('meal.macroGap') }}
    </p>
    <div class="grid gap-2 sm:grid-cols-2">
      <Button type="button" class="h-11" @click="emit('edit')">{{ t('meal.edit') }}</Button>
      <Button type="button" variant="destructive" class="h-11" @click="emit('remove')">{{ t('meal.delete') }}</Button>
    </div>
  </div>
</template>
