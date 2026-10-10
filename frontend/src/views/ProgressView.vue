<script setup lang="ts">
import { computed } from 'vue'
import { Minus, TrendingDown, TrendingUp } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Empty, EmptyDescription, EmptyHeader, EmptyTitle } from '@/components/ui/empty'
import { Input } from '@/components/ui/input'
import BarCompare from '@/components/charts/BarCompare.vue'
import MacroDonut from '@/components/charts/MacroDonut.vue'
import WeightChart from '@/components/charts/WeightChart.vue'
import FormField from '@/components/forms/FormField.vue'
import { useI18n } from '@/composables/useI18n'
import { addDays, eachDay } from '@/lib/dates'
import { formatKcal, formatNumber, formatShortDate, formatWeekday } from '@/lib/format'
import { useAppStore } from '@/stores/app'
import type { Period } from '@/types/domain'

const app = useAppStore()
const { t } = useI18n()

const options: { id: Period; label: 'reports.period7' | 'reports.period30' | 'reports.period90' | 'reports.custom' }[] = [
  { id: '7', label: 'reports.period7' },
  { id: '30', label: 'reports.period30' },
  { id: '90', label: 'reports.period90' },
  { id: 'custom', label: 'reports.custom' },
]

const range = computed(() => {
  if (app.period === 'custom') {
    if (app.customFrom > app.customTo) return { invalid: true as const, days: [] as string[] }
    const days = eachDay(app.customFrom, app.customTo).filter((day) => day <= app.today && day >= app.earliest)
    return { invalid: false as const, days }
  }
  const length = Number(app.period)
  return { invalid: false as const, days: eachDay(addDays(app.today, -(length - 1)), app.today) }
})

const stats = computed(() => {
  const days = range.value.invalid ? [] : range.value.days
  const rows = days.map((day) => app.nutrition(day))
  const count = rows.length || 1
  const intake = rows.reduce((sum, row) => sum + row.intake, 0) / count
  const expenditure = rows.reduce((sum, row) => sum + row.expenditure, 0) / count
  return {
    intake,
    expenditure,
    balance: intake - expenditure,
    protein: rows.reduce((sum, row) => sum + row.protein, 0) / count,
    carbs: rows.reduce((sum, row) => sum + row.carbs, 0) / count,
    fat: rows.reduce((sum, row) => sum + row.fat, 0) / count,
    logged: rows.filter((row) => row.logged).length,
    total: rows.length,
  }
})

const bars = computed(() => {
  const days = range.value.invalid ? [] : range.value.days
  if (days.length <= 14) {
    return {
      weekly: false,
      points: days.map((day) => {
        const nutrition = app.nutrition(day)
        return {
          label: days.length <= 7 ? formatWeekday(day, app.locale) : formatShortDate(day, app.locale),
          intake: nutrition.intake,
          expenditure: nutrition.expenditure,
        }
      }),
    }
  }
  const points: { label: string; intake: number; expenditure: number }[] = []
  for (let index = 0; index < days.length; index += 7) {
    const slice = days.slice(index, index + 7)
    const rows = slice.map((day) => app.nutrition(day))
    points.push({
      label: formatShortDate(slice[0], app.locale),
      intake: rows.reduce((sum, row) => sum + row.intake, 0) / rows.length,
      expenditure: rows.reduce((sum, row) => sum + row.expenditure, 0) / rows.length,
    })
  }
  return { weekly: true, points }
})

const weightPoints = computed(() => {
  const days = range.value.invalid ? [] : range.value.days
  if (days.length === 0) return []
  const start = days[0]
  const end = days[days.length - 1]
  return app.weights.filter((point) => point.date >= start && point.date <= end).sort((a, b) => a.date.localeCompare(b.date))
})

const balanceLabel = computed(() => {
  if (Math.abs(stats.value.balance) < 50) return t('reports.even')
  return stats.value.balance > 0 ? t('reports.surplus') : t('reports.deficit')
})
</script>

<template>
  <div class="grid gap-4">
    <h1 class="text-3xl font-semibold">{{ t('reports.title') }}</h1>
    <div class="flex flex-wrap gap-2" role="group" :aria-label="t('reports.title')">
      <Button
        v-for="option in options"
        :key="option.id"
        type="button"
        class="h-11"
        :variant="app.period === option.id ? 'default' : 'outline'"
        :aria-pressed="app.period === option.id"
        @click="app.period = option.id"
      >
        {{ t(option.label) }}
      </Button>
    </div>
    <div v-if="app.period === 'custom'" class="grid gap-3 sm:grid-cols-2">
      <FormField id="from" :label="t('reports.from')">
        <Input id="from" v-model="app.customFrom" type="date" class="h-11" :min="app.earliest" :max="app.today" />
      </FormField>
      <FormField id="to" :label="t('reports.to')">
        <Input id="to" v-model="app.customTo" type="date" class="h-11" :min="app.earliest" :max="app.today" />
      </FormField>
    </div>
    <p v-if="range.invalid" class="text-sm text-destructive">{{ t('reports.invalidRange') }}</p>
    <Empty v-else-if="range.days.length === 0">
      <EmptyHeader>
        <EmptyTitle>{{ t('reports.title') }}</EmptyTitle>
        <EmptyDescription>{{ t('reports.empty') }}</EmptyDescription>
      </EmptyHeader>
    </Empty>
    <template v-else>
      <div class="grid gap-3 md:grid-cols-3">
        <Card>
          <CardHeader><CardTitle class="text-base">{{ t('reports.avgIntake') }}</CardTitle></CardHeader>
          <CardContent><p class="text-2xl font-semibold text-energy-in">{{ formatKcal(stats.intake, app.locale) }}</p></CardContent>
        </Card>
        <Card>
          <CardHeader><CardTitle class="text-base">{{ t('reports.avgOut') }}</CardTitle></CardHeader>
          <CardContent><p class="text-2xl font-semibold text-energy-out">{{ formatKcal(stats.expenditure, app.locale) }}</p></CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle class="text-base">{{ t('reports.balance') }}</CardTitle>
            <CardDescription>{{ t('reports.balanceHelp') }}</CardDescription>
          </CardHeader>
          <CardContent class="flex items-center gap-2">
            <TrendingUp v-if="stats.balance > 50" class="text-warning" />
            <TrendingDown v-else-if="stats.balance < -50" class="text-primary" />
            <Minus v-else />
            <div>
              <p class="font-semibold">{{ balanceLabel }}</p>
              <p>{{ formatNumber(Math.abs(stats.balance), app.locale) }} kcal</p>
            </div>
          </CardContent>
        </Card>
      </div>
      <Card>
        <CardHeader><CardTitle>{{ t('reports.intakeChart') }}</CardTitle></CardHeader>
        <CardContent><BarCompare :points="bars.points" :weekly="bars.weekly" /></CardContent>
      </Card>
      <Card>
        <CardHeader><CardTitle>{{ t('reports.weightChart') }}</CardTitle></CardHeader>
        <CardContent><WeightChart :points="weightPoints" /></CardContent>
      </Card>
      <Card>
        <CardHeader><CardTitle>{{ t('reports.macros') }}</CardTitle></CardHeader>
        <CardContent><MacroDonut :protein="stats.protein" :carbs="stats.carbs" :fat="stats.fat" /></CardContent>
      </Card>
      <Card>
        <CardHeader>
          <CardTitle>{{ t('reports.consistency') }}</CardTitle>
          <CardDescription>{{ t('reports.loggedDays', { logged: stats.logged, total: stats.total }) }}</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="flex flex-wrap gap-1" :aria-label="t('reports.consistency')">
            <span
              v-for="day in range.days"
              :key="day"
              class="size-4 rounded-sm"
              :class="app.nutrition(day).logged ? 'bg-primary' : 'bg-muted'"
              :title="`${formatShortDate(day, app.locale)} ${app.nutrition(day).logged ? t('reports.loggedDays', { logged: 1, total: 1 }) : ''}`"
            />
          </div>
        </CardContent>
      </Card>
    </template>
  </div>
</template>
