<script setup lang="ts">
import { computed, ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Empty, EmptyDescription, EmptyHeader, EmptyTitle } from '@/components/ui/empty'
import WeightChart from '@/components/charts/WeightChart.vue'
import ConfirmDialog from '@/components/layout/ConfirmDialog.vue'
import ResponsivePanel from '@/components/layout/ResponsivePanel.vue'
import WeightForm from '@/components/weight/WeightForm.vue'
import { useI18n } from '@/composables/useI18n'
import { addDays } from '@/lib/dates'
import { formatShortDate, formatWeight } from '@/lib/format'
import { useAppStore } from '@/stores/app'
import type { WeightPoint, WeightRange } from '@/types/domain'

const app = useAppStore()
const { t } = useI18n()
const editing = ref<WeightPoint | null>(null)
const deleting = ref<WeightPoint | null>(null)
const open = ref(false)
const deleteOpen = computed({
  get: () => deleting.value != null,
  set: (value: boolean) => {
    if (!value) deleting.value = null
  },
})

const ranges: { id: WeightRange; label: 'weight.range7' | 'weight.range30' | 'weight.range90' | 'weight.range1y'; days: number }[] = [
  { id: '7d', label: 'weight.range7', days: 7 },
  { id: '30d', label: 'weight.range30', days: 30 },
  { id: '90d', label: 'weight.range90', days: 90 },
  { id: '1y', label: 'weight.range1y', days: 365 },
]

const points = computed(() => {
  const range = ranges.find((item) => item.id === app.weightRange) ?? ranges[1]
  const start = addDays(app.today, -(range.days - 1))
  return [...app.weights].filter((point) => point.date >= start && point.date <= app.today).sort((a, b) => a.date.localeCompare(b.date))
})

function create() {
  editing.value = { id: '', date: app.selectedDate, kg: app.profile.weightKg || 70, notes: '' }
  open.value = true
}

function edit(point: WeightPoint) {
  editing.value = { ...point }
  open.value = true
}

function save(point: WeightPoint) {
  app.upsertWeight(point)
  open.value = false
}
</script>

<template>
  <div class="grid gap-4">
    <h1 class="text-3xl font-semibold">{{ t('weight.title') }}</h1>
    <Card>
      <CardHeader>
        <CardTitle>{{ t('weight.current') }}</CardTitle>
      </CardHeader>
      <CardContent class="grid gap-1">
        <p class="text-3xl font-semibold">{{ app.latestWeight ? formatWeight(app.latestWeight.kg, app.profile.units, app.locale) : '—' }}</p>
        <p class="text-sm text-muted-foreground">{{ app.latestWeight ? formatShortDate(app.latestWeight.date, app.locale) : t('weight.date') }}</p>
        <p class="text-sm">{{ t('weight.goal') }}: {{ app.profile.targetWeightKg ? formatWeight(app.profile.targetWeightKg, app.profile.units, app.locale) : t('weight.goalEmpty') }}</p>
      </CardContent>
    </Card>
    <div class="flex flex-wrap gap-2" role="group" :aria-label="t('weight.title')">
      <Button
        v-for="range in ranges"
        :key="range.id"
        type="button"
        class="h-11"
        :variant="app.weightRange === range.id ? 'default' : 'outline'"
        :aria-pressed="app.weightRange === range.id"
        @click="app.weightRange = range.id"
      >
        {{ t(range.label) }}
      </Button>
    </div>
    <Card>
      <CardHeader>
        <CardTitle>{{ t('reports.weightChart') }}</CardTitle>
      </CardHeader>
      <CardContent>
        <WeightChart :points="points" />
      </CardContent>
    </Card>
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold">{{ t('weight.history') }}</h2>
    </div>
    <Empty v-if="points.length === 0">
      <EmptyHeader>
        <EmptyTitle>{{ t('weight.history') }}</EmptyTitle>
        <EmptyDescription>{{ t('weight.empty') }}</EmptyDescription>
      </EmptyHeader>
    </Empty>
    <ul v-else class="grid gap-2">
      <li v-for="point in [...points].reverse()" :key="point.id" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border bg-card p-3">
        <div>
          <p class="font-medium">{{ formatWeight(point.kg, app.profile.units, app.locale) }}</p>
          <p class="text-sm text-muted-foreground">{{ formatShortDate(point.date, app.locale) }}</p>
          <p v-if="point.notes" class="text-sm">{{ point.notes }}</p>
        </div>
        <div class="flex gap-2">
          <Button type="button" variant="outline" class="h-11" @click="edit(point)">{{ t('common.edit') }}</Button>
          <Button type="button" variant="destructive" class="h-11" @click="deleting = point">{{ t('common.delete') }}</Button>
        </div>
      </li>
    </ul>
    <Button type="button" class="h-11 w-full hover:bg-primary-hover" @click="create">{{ t('weight.add') }}</Button>
    <ResponsivePanel v-model:open="open" :title="editing?.id ? t('weight.edit') : t('weight.add')" :description="t('demo.savedBody')">
      <WeightForm v-if="editing" :point="editing" @save="save" @cancel="open = false" />
    </ResponsivePanel>
    <ConfirmDialog v-model:open="deleteOpen" :title="t('weight.deleteTitle')" :description="t('weight.deleteBody')" :confirm-label="t('common.delete')" @confirm="deleting && app.deleteWeight(deleting.id)" />
  </div>
</template>
