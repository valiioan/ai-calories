<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from '@/composables/useI18n'
import { formatNumber } from '@/lib/format'
import { useAppStore } from '@/stores/app'

const props = defineProps<{ protein: number; carbs: number; fat: number }>()
const app = useAppStore()
const { t } = useI18n()

const parts = computed(() => {
  const rows = [
    { key: 'dashboard.protein' as const, value: props.protein, color: 'var(--chart-3)' },
    { key: 'dashboard.carbs' as const, value: props.carbs, color: 'var(--energy-in)' },
    { key: 'dashboard.fat' as const, value: props.fat, color: 'var(--warning)' },
  ]
  const total = rows.reduce((sum, row) => sum + row.value, 0) || 1
  let cursor = 0
  return rows.map((row) => {
    const fraction = row.value / total
    const start = cursor
    cursor += fraction
    return { ...row, fraction, start, percent: Math.round(fraction * 100) }
  })
})

const gradient = computed(() => {
  const stops = parts.value.flatMap((part) => [
    `${part.color} ${part.start * 100}%`,
    `${part.color} ${(part.start + part.fraction) * 100}%`,
  ])
  return `conic-gradient(${stops.join(', ')})`
})
</script>

<template>
  <div class="flex flex-wrap items-center gap-6">
    <div class="grid size-28 place-items-center rounded-full" :style="{ background: gradient }" role="img" :aria-label="t('reports.macros')">
      <span class="grid size-16 place-items-center rounded-full bg-card text-sm font-semibold">kcal</span>
    </div>
    <ul class="grid min-w-40 flex-1 gap-2 text-sm">
      <li v-for="part in parts" :key="part.key" class="flex items-center justify-between gap-3">
        <span class="inline-flex items-center gap-2">
          <span class="size-3 rounded-sm" :style="{ background: part.color }" />
          {{ t(part.key) }}
        </span>
        <span class="font-medium">{{ formatNumber(part.value, app.locale) }} g · {{ part.percent }}%</span>
      </li>
    </ul>
  </div>
</template>
