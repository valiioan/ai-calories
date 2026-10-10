<script setup lang="ts">
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card'
import { useI18n } from '@/composables/useI18n'
import { mealCalories } from '@/fixtures/demo'
import { formatKcal, formatLongDate, formatNumber, localizedName } from '@/lib/format'
import { useAppStore } from '@/stores/app'
import type { Proposal } from '@/types/domain'

defineProps<{ proposal: Proposal }>()
const emit = defineEmits<{ confirm: []; cancel: []; correct: [] }>()
const app = useAppStore()
const { t } = useI18n()

function macro(value: number | null) {
  return value == null ? '—' : `${formatNumber(value, app.locale)} g`
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle class="flex flex-wrap items-center justify-between gap-2 text-base">
        <span v-if="proposal.kind === 'meal'">{{ t(`meal.${proposal.meal.type}`) }} · {{ formatLongDate(proposal.meal.date, app.locale) }}</span>
        <span v-else-if="proposal.kind === 'weight'">{{ t('chat.weightLabel') }}</span>
        <span v-else-if="proposal.kind === 'activity'">{{ t('chat.activityLabel') }}</span>
        <span v-else>{{ t('meal.delete') }}</span>
        <Badge variant="secondary">
          <template v-if="proposal.status === 'pending' && proposal.kind === 'meal' && proposal.corrected">{{ t('chat.correctedBadge') }}</template>
          <template v-else-if="proposal.status === 'pending'">{{ t('chat.unconfirmed') }}</template>
          <template v-else-if="proposal.status === 'confirmed'">{{ t('chat.confirmedBadge') }}</template>
          <template v-else>{{ t('chat.cancelled') }}</template>
        </Badge>
      </CardTitle>
    </CardHeader>
    <CardContent class="grid gap-3">
      <template v-if="proposal.kind === 'meal'">
        <ul class="grid gap-3">
          <li v-for="item in proposal.meal.items" :key="item.id" class="flex items-start justify-between gap-3 border-b border-border pb-2">
            <div>
              <p class="font-medium">{{ localizedName(item, app.locale) }}</p>
              <p class="text-sm text-muted-foreground">{{ item.quantity }} {{ t(`unit.${item.unit}`) }}</p>
              <p class="text-xs text-muted-foreground">
                {{ t('dashboard.protein') }} {{ macro(item.protein) }} ·
                {{ t('dashboard.carbs') }} {{ macro(item.carbs) }} ·
                {{ t('dashboard.fat') }} {{ macro(item.fat) }}
              </p>
            </div>
            <p class="font-medium text-energy-in">{{ formatKcal(item.calories, app.locale) }}</p>
          </li>
        </ul>
        <p class="flex items-center justify-between text-base font-semibold">
          <span>{{ t('chat.total') }}</span>
          <span class="text-primary">{{ formatKcal(mealCalories(proposal.meal), app.locale) }}</span>
        </p>
        <p class="text-sm text-muted-foreground"><span class="font-medium text-foreground">{{ t('chat.assumptions') }}:</span> {{ t(proposal.assumptionsKey) }}</p>
        <p v-if="proposal.uncertaintyKey" class="text-sm"><span class="font-medium">{{ t('chat.uncertainty') }}:</span> {{ t(proposal.uncertaintyKey) }}</p>
        <p class="text-sm text-muted-foreground"><span class="font-medium text-foreground">{{ t('chat.sources') }}:</span> {{ t(proposal.sourcesKey) }}</p>
      </template>
      <template v-else-if="proposal.kind === 'weight'">
        <p class="text-2xl font-semibold">{{ proposal.kg }} kg</p>
        <p class="text-sm text-muted-foreground">{{ formatLongDate(proposal.date, app.locale) }}</p>
      </template>
      <template v-else-if="proposal.kind === 'activity'">
        <p class="font-medium">{{ app.locale === 'en' ? proposal.activity.nameEn || proposal.activity.name : proposal.activity.name }}</p>
        <p class="text-sm text-muted-foreground">{{ t('activity.minutes', { count: proposal.activity.minutes }) }} · {{ t(`activity.${proposal.activity.intensity}`) }}</p>
        <p class="text-sm text-energy-out">{{ formatKcal(proposal.activity.calories, app.locale) }} · {{ t('activity.caloriesHelp') }}</p>
      </template>
      <template v-else>
        <p>{{ proposal.label }}</p>
      </template>
    </CardContent>
    <CardFooter v-if="proposal.status === 'pending'" class="grid gap-2 sm:grid-cols-3">
      <Button type="button" class="h-11 hover:bg-primary-hover" @click="emit('confirm')">{{ t('chat.confirm') }}</Button>
      <Button v-if="proposal.kind !== 'delete-meal'" type="button" variant="outline" class="h-11" @click="emit('correct')">{{ t('chat.correct') }}</Button>
      <Button type="button" variant="ghost" class="h-11" @click="emit('cancel')">{{ t('chat.cancel') }}</Button>
    </CardFooter>
  </Card>
</template>
