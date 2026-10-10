<script setup lang="ts">
import { computed, ref } from 'vue'
import { CalendarDate, parseDate, type DateValue } from '@internationalized/date'
import { Calendar } from '@/components/ui/calendar'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { useI18n } from '@/composables/useI18n'
import { parseIso } from '@/lib/dates'
import { cn } from '@/lib/utils'
import { useAppStore } from '@/stores/app'

const model = defineModel<string>({ required: true })
const props = withDefaults(defineProps<{ id?: string; invalid?: boolean }>(), { id: 'dob' })

const open = ref(false)
const app = useAppStore()
const { t } = useI18n()
const minDate = new CalendarDate(1900, 1, 1)
const maxDate = new CalendarDate(2015, 1, 1)
const fallbackDate = new CalendarDate(1995, 6, 15)

const selected = computed<DateValue | undefined>({
  get() {
    return model.value ? parseDate(model.value) : undefined
  },
  set(date) {
    if (!date) return
    model.value = date.toString()
    open.value = false
  },
})

function keepCalendarOpen(event: Event) {
  const target = event.target
  if (target instanceof Element && target.closest('[data-slot=select-content]')) event.preventDefault()
}

const localeTag = computed(() => (app.locale === 'ro' ? 'ro-RO' : 'en-GB'))
const label = computed(() => {
  if (!model.value) return t('onboarding.pickDate')
  return new Intl.DateTimeFormat(localeTag.value, {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  }).format(parseIso(model.value))
})
</script>

<template>
  <Popover v-model:open="open">
    <PopoverTrigger
      :id="props.id"
      :aria-invalid="invalid || undefined"
      :aria-expanded="open"
      :class="cn(
        'border-input focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive flex h-11 w-full items-center rounded-lg border bg-transparent px-2.5 text-left text-base outline-none focus-visible:ring-3 md:text-sm',
        !model && 'text-muted-foreground',
      )"
    >
      {{ label }}
    </PopoverTrigger>
    <PopoverContent
      align="start"
      class="w-fit"
      @interact-outside="keepCalendarOpen"
      @pointer-down-outside="keepCalendarOpen"
      @focus-outside="keepCalendarOpen"
    >
      <Calendar
        v-model="selected"
        :default-placeholder="selected ?? fallbackDate"
        layout="month-and-year"
        :calendar-label="t('onboarding.dob')"
        :locale="localeTag"
        :min-value="minDate"
        :max-value="maxDate"
      />
    </PopoverContent>
  </Popover>
</template>
