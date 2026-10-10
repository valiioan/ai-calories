<script setup lang="ts">
import { computed } from 'vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import DayNavigator from '@/components/layout/DayNavigator.vue'
import MealSection from '@/components/meals/MealSection.vue'
import { useI18n } from '@/composables/useI18n'
import { formatKcal } from '@/lib/format'
import { useAppStore } from '@/stores/app'

const app = useAppStore()
const { t } = useI18n()
const day = computed(() => app.todayNutrition)
</script>

<template>
  <div class="grid gap-4">
    <div>
      <h1 class="text-3xl font-semibold">{{ t('nav.journal') }}</h1>
    </div>
    <DayNavigator calendar />
    <Card>
      <CardHeader>
        <CardTitle>{{ t('dashboard.goal') }}</CardTitle>
      </CardHeader>
      <CardContent class="flex flex-wrap justify-between gap-2 text-sm">
        <span>{{ t('reports.legendIntake') }}: {{ formatKcal(day.intake, app.locale) }}</span>
        <span>{{ t('dashboard.target') }}: {{ app.profile.calorieTarget ? formatKcal(app.profile.calorieTarget, app.locale) : '—' }}</span>
      </CardContent>
    </Card>
    <MealSection :date="app.selectedDate" manual />
  </div>
</template>
