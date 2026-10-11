<script setup lang="ts">
import { computed, ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Empty, EmptyDescription, EmptyHeader, EmptyTitle } from '@/components/ui/empty'
import ActivityForm from '@/components/activities/ActivityForm.vue'
import ConfirmDialog from '@/components/layout/ConfirmDialog.vue'
import DayNavigator from '@/components/layout/DayNavigator.vue'
import ResponsivePanel from '@/components/layout/ResponsivePanel.vue'
import { useI18n } from '@/composables/useI18n'
import { formatKcal, localizedName } from '@/lib/format'
import { useAppStore } from '@/stores/app'
import type { Activity } from '@/types/domain'

const app = useAppStore()
const { t } = useI18n()
const editing = ref<Activity | null>(null)
const deleting = ref<Activity | null>(null)
const open = ref(false)
const deleteOpen = computed({
  get: () => deleting.value != null,
  set: (value: boolean) => {
    if (!value) deleting.value = null
  },
})

const items = computed(() => app.activities.filter((item) => item.date === app.selectedDate))
const day = computed(() => app.todayNutrition)

function create() {
  editing.value = {
    id: '',
    date: app.selectedDate,
    name: '',
    minutes: 30,
    intensity: 'moderate',
    calories: 150,
    notes: '',
  }
  open.value = true
}

function edit(activity: Activity) {
  editing.value = { ...activity }
  open.value = true
}

  function save(activity: Activity) {
  app.upsertActivity(activity)
  open.value = false
}
</script>

<template>
  <div class="grid gap-4">
    <h1 class="text-3xl font-semibold">{{ t('activity.title') }}</h1>
    <DayNavigator calendar />
    <div class="grid gap-3 md:grid-cols-2">
      <Card>
        <CardHeader>
          <CardTitle>{{ t('activity.summaryTee') }}</CardTitle>
          <CardDescription>{{ t('activity.summaryTeeHelp') }}</CardDescription>
        </CardHeader>
        <CardContent>
          <p class="text-3xl font-semibold text-energy-out">{{ formatKcal(day.expenditure, app.locale) }}</p>
        </CardContent>
      </Card>
      <Card>
        <CardHeader>
          <CardTitle>{{ t('activity.summaryExercise') }}</CardTitle>
          <CardDescription>{{ t('activity.summaryExerciseHelp') }}</CardDescription>
        </CardHeader>
        <CardContent>
          <p class="text-3xl font-semibold">{{ formatKcal(items.reduce((sum, item) => sum + item.calories, 0), app.locale) }}</p>
        </CardContent>
      </Card>
    </div>
    <div class="flex items-center justify-between gap-2">
      <h2 class="text-lg font-semibold">{{ t('activity.list') }}</h2>
    </div>
    <Empty v-if="items.length === 0">
      <EmptyHeader>
        <EmptyTitle>{{ t('activity.title') }}</EmptyTitle>
        <EmptyDescription>{{ t('activity.empty') }}</EmptyDescription>
      </EmptyHeader>
    </Empty>
    <ul v-else class="grid gap-2">
      <li v-for="item in items" :key="item.id" class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border bg-card p-3">
        <div>
          <p class="font-medium">{{ localizedName(item, app.locale) }}</p>
          <p class="text-sm text-muted-foreground">{{ t('activity.minutes', { count: item.minutes }) }} · {{ t(`activity.${item.intensity}`) }}</p>
          <p class="text-sm text-energy-out">{{ formatKcal(item.calories, app.locale) }}</p>
        </div>
        <div class="flex gap-2">
          <Button type="button" variant="outline" class="h-11" @click="edit(item)">{{ t('common.edit') }}</Button>
          <Button type="button" variant="destructive" class="h-11" @click="deleting = item">{{ t('common.delete') }}</Button>
        </div>
      </li>
    </ul>
    <Button type="button" class="h-11 w-full hover:bg-primary-hover" @click="create">{{ t('activity.add') }}</Button>
    <ResponsivePanel v-model:open="open" :title="editing?.id ? t('activity.edit') : t('activity.add')" :description="t('activity.caloriesHelp')">
      <ActivityForm v-if="editing" :activity="editing" @save="save" @cancel="open = false" />
    </ResponsivePanel>
    <ConfirmDialog v-model:open="deleteOpen" :title="t('activity.deleteTitle')" :description="t('activity.deleteBody')" :confirm-label="t('common.delete')" @confirm="deleting && app.deleteActivity(deleting.id)" />
  </div>
</template>
