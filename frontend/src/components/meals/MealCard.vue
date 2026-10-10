<script setup lang="ts">
import { EllipsisVertical } from '@lucide/vue'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import { Card, CardContent } from '@/components/ui/card'
import { useI18n } from '@/composables/useI18n'
import { formatKcal, localizedName } from '@/lib/format'
import { mealCalories } from '@/fixtures/demo'
import { useAppStore } from '@/stores/app'
import type { Meal } from '@/types/domain'

defineProps<{ meal: Meal }>()
const emit = defineEmits<{ details: []; edit: []; remove: [] }>()
const app = useAppStore()
const { t } = useI18n()
</script>

<template>
  <Card size="sm">
    <CardContent class="flex items-start gap-3">
      <button type="button" class="grid min-w-0 flex-1 gap-1 text-left" @click="emit('details')">
        <span class="text-sm font-semibold">{{ t(`meal.${meal.type}`) }} · {{ meal.time }}</span>
        <span class="truncate text-sm text-muted-foreground">
          {{ meal.items.map((item) => localizedName(item, app.locale)).join(', ') }}
        </span>
        <span class="text-sm font-medium text-energy-in">
          <span class="sr-only">{{ t('meal.calories') }} </span>{{ formatKcal(mealCalories(meal), app.locale) }}
        </span>
      </button>
      <DropdownMenu>
        <DropdownMenuTrigger class="grid size-11 shrink-0 place-items-center rounded-lg hover:bg-muted" :aria-label="t('meal.menu')">
          <EllipsisVertical />
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuItem class="min-h-11" @click="emit('details')">{{ t('meal.details') }}</DropdownMenuItem>
          <DropdownMenuItem class="min-h-11" @click="emit('edit')">{{ t('meal.edit') }}</DropdownMenuItem>
          <DropdownMenuItem class="min-h-11" variant="destructive" @click="emit('remove')">{{ t('meal.delete') }}</DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </CardContent>
  </Card>
</template>
