<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { ArrowUpRight, Info, TriangleAlert } from '@lucide/vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import BarCompare from '@/components/charts/BarCompare.vue'
import DayNavigator from '@/components/layout/DayNavigator.vue'
import ProposalCard from '@/components/chat/ProposalCard.vue'
import MealSection from '@/components/meals/MealSection.vue'
import { useI18n } from '@/composables/useI18n'
import { addDays } from '@/lib/dates'
import { formatKcal, formatNumber, formatShortDate, formatWeight } from '@/lib/format'
import { useAppStore } from '@/stores/app'

const app = useAppStore()
const router = useRouter()
const { t } = useI18n()

const day = computed(() => app.todayNutrition)
const target = computed(() => app.profile.calorieTarget)
const percent = computed(() => (target.value ? Math.round((day.value.intake / target.value) * 100) : 0))
const ring = computed(() => Math.min(1, target.value ? day.value.intake / target.value : 0))
const remaining = computed(() => (target.value == null ? 0 : target.value - day.value.intake))
const circumference = 2 * Math.PI * 42
const week = computed(() => {
  const start = addDays(app.selectedDate, -6)
  const points = []
  let cursor = start
  while (cursor <= app.selectedDate) {
    const nutrition = app.nutrition(cursor)
    points.push({
      label: formatShortDate(cursor, app.locale),
      intake: nutrition.intake,
      expenditure: nutrition.expenditure,
    })
    cursor = addDays(cursor, 1)
  }
  return points
})
</script>

<template>
  <div class="grid gap-4">
    <DayNavigator />
    <div>
      <p class="text-sm text-muted-foreground">{{ t(app.freshAccount ? 'dashboard.welcome' : 'dashboard.welcomeBack', { name: app.profile.firstName || app.user?.firstName || '' }) }}</p>
      <h1 class="text-3xl font-semibold">{{ t('dashboard.today') }}</h1>
    </div>

    <Card v-if="target">
      <CardHeader>
        <CardTitle>{{ t('dashboard.goal') }}</CardTitle>
        <CardAction>
          <Badge variant="secondary">{{ t('dashboard.percent', { percent }) }}</Badge>
        </CardAction>
      </CardHeader>
      <CardContent class="grid gap-4">
        <div class="flex flex-wrap items-center gap-4">
          <svg viewBox="0 0 100 100" class="size-32 shrink-0" aria-hidden="true">
            <circle cx="50" cy="50" r="42" fill="none" stroke="var(--muted)" stroke-width="10" />
            <circle
              cx="50"
              cy="50"
              r="42"
              fill="none"
              stroke="var(--primary)"
              stroke-width="10"
              stroke-linecap="round"
              :stroke-dasharray="circumference"
              :stroke-dashoffset="circumference * (1 - ring)"
              transform="rotate(-90 50 50)"
            />
            <text x="50" y="56" text-anchor="middle" fill="currentColor" font-size="18" font-weight="700">{{ formatNumber(day.intake, app.locale) }}</text>
          </svg>
          <div>
            <p class="text-3xl font-semibold">{{ formatNumber(Math.abs(remaining), app.locale) }}</p>
            <p v-if="remaining >= 0" class="text-sm text-muted-foreground">{{ t('dashboard.remaining') }}</p>
            <p v-else class="inline-flex items-center gap-1 text-sm">
              <TriangleAlert class="size-4 text-warning" />
              {{ t('dashboard.over', { kcal: formatNumber(Math.abs(remaining), app.locale) }) }}
            </p>
          </div>
        </div>
        <div class="flex flex-wrap justify-between gap-2 text-sm">
          <span>{{ t('dashboard.consumed') }}: {{ formatKcal(day.intake, app.locale) }}</span>
          <span>{{ t('dashboard.target') }}: {{ formatKcal(target, app.locale) }}</span>
        </div>
        <p class="flex gap-2 text-sm text-muted-foreground">
          <Info class="mt-0.5 size-4 shrink-0" />
          {{ t('dashboard.ringNote') }}
        </p>
      </CardContent>
    </Card>
    <Card v-else>
      <CardHeader>
        <CardTitle>{{ t('dashboard.goal') }}</CardTitle>
        <CardDescription>{{ t('dashboard.noTarget') }}</CardDescription>
      </CardHeader>
      <CardContent>
        <Button as-child class="h-11">
          <RouterLink to="/profil">{{ t('dashboard.setTarget') }}</RouterLink>
        </Button>
      </CardContent>
    </Card>

    <div class="grid grid-cols-2 gap-3">
      <Card size="sm">
        <CardHeader>
          <CardTitle class="text-sm">{{ t('dashboard.outTitle') }}</CardTitle>
          <CardAction>
            <Badge>{{ t('dashboard.outBadge') }}</Badge>
          </CardAction>
        </CardHeader>
        <CardContent class="grid gap-1">
          <p class="text-2xl font-semibold text-energy-out">{{ formatKcal(day.expenditure, app.locale) }}</p>
          <p class="text-xs text-muted-foreground">{{ t('dashboard.outDaily') }}</p>
          <RouterLink to="/activitati" class="text-xs underline-offset-4 hover:underline">
            {{ t('dashboard.outActivity', { kcal: formatNumber(app.exerciseCalories, app.locale) }) }}
          </RouterLink>
        </CardContent>
      </Card>
      <Card size="sm">
        <CardHeader>
          <CardTitle class="text-sm">{{ t('dashboard.weightTitle') }}</CardTitle>
        </CardHeader>
        <CardContent class="grid gap-1">
          <p class="text-2xl font-semibold">{{ app.latestWeight ? formatWeight(app.latestWeight.kg, app.profile.units, app.locale) : '—' }}</p>
          <p class="text-xs text-muted-foreground">{{ app.latestWeight ? formatShortDate(app.latestWeight.date, app.locale) : t('dashboard.weightLatest') }}</p>
          <RouterLink to="/greutate" class="inline-flex min-h-11 items-center text-sm text-primary">{{ t('dashboard.weightLink') }}</RouterLink>
        </CardContent>
      </Card>
    </div>

    <RouterLink v-if="app.pendingMeals.length === 0" to="/chat" class="flex min-h-16 items-center gap-3 rounded-2xl bg-secondary px-4 py-3 text-secondary-foreground">
      <span class="grid size-11 place-items-center rounded-xl bg-primary text-primary-foreground"><ArrowUpRight /></span>
      <span>
        <span class="block font-semibold">{{ t('dashboard.aiTitle') }}</span>
        <span class="block text-sm">{{ t('dashboard.aiHint') }}</span>
      </span>
    </RouterLink>
    <ProposalCard
      v-for="proposal in app.pendingMeals"
      :key="proposal.id"
      :proposal="proposal"
      @confirm="app.confirmProposal(proposal.id)"
      @cancel="app.cancelProposal(proposal.id)"
      @correct="router.push({ name: 'chat' })"
    />

    <section class="grid gap-3">
      <div class="flex items-center justify-between gap-2">
        <h2 class="text-lg font-semibold">{{ t('dashboard.meals') }}</h2>
        <RouterLink to="/jurnal" class="text-sm text-primary">{{ t('dashboard.viewJournal') }}</RouterLink>
      </div>
      <MealSection :date="app.selectedDate" />
    </section>

    <Card>
      <CardHeader>
        <CardTitle>{{ t('dashboard.macros') }}</CardTitle>
      </CardHeader>
      <CardContent class="grid grid-cols-3 gap-2 text-sm">
        <p><span class="block text-muted-foreground">{{ t('dashboard.protein') }}</span><span class="font-semibold">{{ formatNumber(day.protein, app.locale) }} g</span></p>
        <p><span class="block text-muted-foreground">{{ t('dashboard.carbs') }}</span><span class="font-semibold">{{ formatNumber(day.carbs, app.locale) }} g</span></p>
        <p><span class="block text-muted-foreground">{{ t('dashboard.fat') }}</span><span class="font-semibold">{{ formatNumber(day.fat, app.locale) }} g</span></p>
      </CardContent>
    </Card>

    <Card class="hidden md:block">
      <CardHeader>
        <CardTitle>{{ t('dashboard.weekChart') }}</CardTitle>
      </CardHeader>
      <CardContent>
        <BarCompare :points="week" />
      </CardContent>
    </Card>
  </div>
</template>
