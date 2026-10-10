<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from '@/composables/useI18n'
import { formatShortDate } from '@/lib/format'
import { useAppStore } from '@/stores/app'
import type { WeightPoint } from '@/types/domain'

const props = defineProps<{ points: WeightPoint[] }>()
const app = useAppStore()
const { t } = useI18n()
const active = ref(Math.max(0, props.points.length - 1))

const trend = computed(() => {
  const values = props.points.map((point) => point.kg)
  return values.map((_, index) => {
    const slice = values.slice(Math.max(0, index - 2), index + 3)
    return slice.reduce((sum, value) => sum + value, 0) / slice.length
  })
})

const layout = computed(() => {
  const values = [...props.points.map((point) => point.kg), ...trend.value]
  const min = Math.min(...values) - 0.3
  const max = Math.max(...values) + 0.3
  const span = Math.max(0.1, max - min)
  return props.points.map((point, index) => {
    const x = props.points.length === 1 ? 50 : (index / (props.points.length - 1)) * 100
    const y = 36 - ((point.kg - min) / span) * 32
    const trendY = 36 - ((trend.value[index] - min) / span) * 32
    return { x, y, trendY }
  })
})

const trendLine = computed(() => layout.value.map((point) => `${point.x},${point.trendY}`).join(' '))
const current = computed(() => props.points[active.value])
const currentTrend = computed(() => trend.value[active.value])

function select(index: number) {
  active.value = index
}
</script>

<template>
  <div v-if="points.length === 0" class="text-sm text-muted-foreground">{{ t('weight.empty') }}</div>
  <div v-else class="grid gap-3">
    <div class="flex flex-wrap gap-4 text-sm">
      <span class="inline-flex items-center gap-2">
        <span class="size-3 rounded-full bg-foreground" />
        {{ t('weight.recorded') }}
      </span>
      <span class="inline-flex items-center gap-2">
        <span class="h-0.5 w-6 border-t-2 border-dashed border-primary" />
        {{ t('weight.trend') }}
      </span>
    </div>
    <svg viewBox="0 0 100 40" class="h-48 w-full" role="img" :aria-label="t('weight.chartLabel')">
      <polyline :points="trendLine" fill="none" stroke="var(--primary)" stroke-width="0.6" stroke-dasharray="1.4 0.9" />
      <circle
        v-for="(point, index) in layout"
        :key="points[index].id"
        :cx="point.x"
        :cy="point.y"
        r="1.1"
        fill="var(--foreground)"
        class="cursor-pointer"
        @mouseenter="select(index)"
        @click="select(index)"
      />
    </svg>
    <div class="flex flex-wrap gap-1">
      <button
        v-for="(point, index) in points"
        :key="point.id"
        type="button"
        class="sr-only focus:not-sr-only focus:rounded-md focus:bg-muted focus:px-2 focus:py-1"
        @focus="select(index)"
      >
        {{ formatShortDate(point.date, app.locale) }}
      </button>
    </div>
    <p v-if="current && currentTrend != null" class="text-sm">
      {{ t('weight.point', {
        date: formatShortDate(current.date, app.locale),
        value: current.kg.toFixed(1),
        trend: currentTrend.toFixed(1),
      }) }}
    </p>
  </div>
</template>
