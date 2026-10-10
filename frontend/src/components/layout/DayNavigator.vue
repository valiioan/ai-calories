<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Calendar, ChevronLeft, ChevronRight } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { useI18n } from '@/composables/useI18n'
import { addDays, calendarCells, monthIso, shiftMonth } from '@/lib/dates'
import { formatLongDate, formatMonth } from '@/lib/format'
import { useAppStore } from '@/stores/app'
import { cn } from '@/lib/utils'

const props = withDefaults(defineProps<{ calendar?: boolean }>(), { calendar: false })
const app = useAppStore()
const { t } = useI18n()
const open = ref(false)
const visibleMonth = ref(monthIso(app.selectedDate))

watch(() => app.selectedDate, (iso) => {
  visibleMonth.value = monthIso(iso)
})

const label = computed(() => formatLongDate(app.selectedDate, app.locale))
const weekdays = computed(() => t('date.weekdays').split(','))
const cells = computed(() => calendarCells(visibleMonth.value))

function pick(iso: string) {
  if (iso > app.today || iso < app.earliest) return
  void app.setDate(iso)
  open.value = false
}

function shift(iso: string, delta: number) {
  return addDays(iso, delta)
}
</script>

<template>
  <div class="flex items-center gap-2">
    <Button type="button" variant="outline" size="icon" class="size-11 shrink-0" :aria-label="t('date.prev')" :disabled="app.selectedDate <= app.earliest" @click="app.setDate(app.selectedDate === app.earliest ? app.earliest : shift(app.selectedDate, -1))">
      <ChevronLeft />
    </Button>
    <p class="min-w-0 flex-1 truncate text-center text-sm font-medium uppercase">{{ label }}</p>
    <Popover v-if="props.calendar" v-model:open="open">
      <PopoverTrigger class="grid size-11 shrink-0 place-items-center rounded-lg border border-border bg-card" :aria-label="t('date.openCalendar')">
        <Calendar />
      </PopoverTrigger>
      <PopoverContent class="w-[min(20rem,calc(100vw-2rem))]">
        <div class="mb-3 flex items-center justify-between gap-2">
          <Button type="button" variant="ghost" size="icon" class="size-11" :aria-label="t('date.prev')" @click="visibleMonth = shiftMonth(visibleMonth, -1)">
            <ChevronLeft />
          </Button>
          <p class="text-sm font-medium capitalize">{{ formatMonth(visibleMonth, app.locale) }}</p>
          <Button type="button" variant="ghost" size="icon" class="size-11" :aria-label="t('date.next')" @click="visibleMonth = shiftMonth(visibleMonth, 1)">
            <ChevronRight />
          </Button>
        </div>
        <div class="grid grid-cols-7 gap-1 text-center text-xs text-muted-foreground">
          <span v-for="day in weekdays" :key="day">{{ day }}</span>
        </div>
        <div class="mt-1 grid grid-cols-7 gap-1">
          <button
            v-for="cell in cells"
            :key="cell.iso"
            type="button"
            class="min-h-11 rounded-md text-sm"
            :class="cn(
              !cell.inMonth && 'text-muted-foreground',
              cell.iso === app.selectedDate && 'bg-primary font-semibold text-primary-foreground',
              (cell.iso > app.today || cell.iso < app.earliest) && 'pointer-events-none opacity-40',
            )"
            :disabled="cell.iso > app.today || cell.iso < app.earliest"
            @click="pick(cell.iso)"
          >
            {{ Number(cell.iso.slice(8)) }}
          </button>
        </div>
      </PopoverContent>
    </Popover>
    <Button type="button" variant="outline" size="icon" class="size-11 shrink-0" :aria-label="t('date.next')" :disabled="app.selectedDate >= app.today" @click="app.setDate(app.selectedDate >= app.today ? app.today : shift(app.selectedDate, 1))">
      <ChevronRight />
    </Button>
  </div>
</template>
