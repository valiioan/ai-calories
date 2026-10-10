<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from '@/composables/useI18n'
import { formatNumber } from '@/lib/format'
import { useAppStore } from '@/stores/app'

const props = defineProps<{
  points: { label: string; intake: number; expenditure: number }[]
  weekly?: boolean
}>()

const app = useAppStore()
const { t } = useI18n()
const active = ref(Math.max(0, props.points.length - 1))

const max = computed(() => Math.max(1, ...props.points.flatMap((point) => [point.intake, point.expenditure])))
const current = computed(() => props.points[active.value] ?? props.points[0])

function height(value: number) {
  return `${Math.max(2, (value / max.value) * 100)}%`
}

function describe(index: number) {
  const point = props.points[index]
  if (!point) return ''
  return t('reports.tooltip', {
    label: point.label,
    intake: formatNumber(point.intake, app.locale),
    out: formatNumber(point.expenditure, app.locale),
  })
}
</script>

<template>
  <div class="grid gap-3">
    <div class="flex flex-wrap gap-4 text-sm">
      <span class="inline-flex items-center gap-2">
        <span class="size-3 rounded-sm bg-energy-in" />
        {{ t('reports.legendIntake') }}
      </span>
      <span class="inline-flex items-center gap-2">
        <span class="size-3 rounded-sm bg-energy-out" />
        {{ t('reports.legendOut') }}
      </span>
    </div>
    <p v-if="weekly" class="text-sm text-muted-foreground">{{ t('reports.weekly') }}</p>
    <div class="flex h-48 items-end gap-1" role="img" :aria-label="t('reports.chartLabel')">
      <button
        v-for="(point, index) in points"
        :key="`${point.label}-${index}`"
        type="button"
        class="flex h-full min-w-0 flex-1 items-end gap-0.5"
        :aria-label="describe(index)"
        :aria-pressed="active === index"
        @mouseenter="active = index"
        @focus="active = index"
        @click="active = index"
      >
        <span class="w-1/2 rounded-t-sm bg-energy-in" :style="{ height: height(point.intake) }" />
        <span class="w-1/2 rounded-t-sm bg-energy-out" :style="{ height: height(point.expenditure) }" />
      </button>
    </div>
    <div class="flex justify-between gap-2 text-xs text-muted-foreground">
      <span class="truncate">{{ points[0]?.label }}</span>
      <span class="truncate">{{ points[points.length - 1]?.label }}</span>
    </div>
    <p v-if="current" class="text-sm">{{ describe(active) }}</p>
    <table class="sr-only">
      <caption>{{ t('reports.chartLabel') }}</caption>
      <tbody>
        <tr v-for="(point, index) in points" :key="`row-${index}`">
          <th scope="row">{{ point.label }}</th>
          <td>{{ point.intake }}</td>
          <td>{{ point.expenditure }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
