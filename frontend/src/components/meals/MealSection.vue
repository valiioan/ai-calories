<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import { Empty, EmptyDescription, EmptyHeader, EmptyTitle } from '@/components/ui/empty'
import ConfirmDialog from '@/components/layout/ConfirmDialog.vue'
import ResponsivePanel from '@/components/layout/ResponsivePanel.vue'
import MealCard from '@/components/meals/MealCard.vue'
import MealDetails from '@/components/meals/MealDetails.vue'
import MealForm from '@/components/meals/MealForm.vue'
import { useI18n } from '@/composables/useI18n'
import { blankMeal, MEAL_TYPES } from '@/lib/meals'
import { formatLongDate } from '@/lib/format'
import { useAppStore } from '@/stores/app'
import type { Meal } from '@/types/domain'

const props = withDefaults(defineProps<{ date: string; manual?: boolean }>(), { manual: false })
const app = useAppStore()
const router = useRouter()
const { t } = useI18n()
const details = ref<Meal | null>(null)
const editing = ref<Meal | null>(null)
const deleting = ref<Meal | null>(null)
const previousDate = ref('')
const detailsOpen = ref(false)
const editOpen = ref(false)
const deleteOpen = computed({
  get: () => deleting.value != null,
  set: (value: boolean) => {
    if (!value) deleting.value = null
  },
})

const groups = computed(() => MEAL_TYPES.map((type) => ({
  type,
  meals: app.mealsOn(props.date).filter((meal) => meal.type === type),
})))

function openDetails(meal: Meal) {
  details.value = meal
  detailsOpen.value = true
}

function openEdit(meal: Meal) {
  editing.value = structuredClone(meal)
  previousDate.value = meal.date
  editOpen.value = true
  detailsOpen.value = false
}

function openCreate() {
  editing.value = blankMeal(props.date)
  previousDate.value = ''
  editOpen.value = true
}

function save(meal: Meal) {
  app.upsertMeal(meal, previousDate.value || undefined)
  editOpen.value = false
}

function confirmDelete() {
  if (!deleting.value) return
  app.deleteMeal(deleting.value.id)
  deleting.value = null
  detailsOpen.value = false
}
</script>

<template>
  <div class="grid gap-3">
    <section v-for="group in groups" :key="group.type" class="grid gap-2">
      <h2 class="text-sm font-semibold">{{ t(`meal.${group.type}`) }}</h2>
      <MealCard
        v-for="meal in group.meals"
        :key="meal.id"
        :meal="meal"
        @details="openDetails(meal)"
        @edit="openEdit(meal)"
        @remove="deleting = meal"
      />
      <Empty v-if="group.meals.length === 0" class="p-4">
        <EmptyHeader>
          <EmptyTitle>{{ t(`meal.${group.type}`) }}</EmptyTitle>
          <EmptyDescription>{{ t('meal.emptyGroup') }}</EmptyDescription>
        </EmptyHeader>
        <Button type="button" variant="outline" class="h-11" @click="router.push({ name: 'chat' })">{{ t('nav.chat') }}</Button>
      </Empty>
    </section>
    <Button v-if="manual" type="button" class="h-11 w-full hover:bg-primary-hover" @click="openCreate">
      {{ t('meal.add') }}
    </Button>
    <ResponsivePanel v-model:open="detailsOpen" :title="details ? t(`meal.${details.type}`) : t('meal.details')" :description="details ? `${formatLongDate(details.date, app.locale)} · ${details.time}` : t('meal.details')">
      <MealDetails v-if="details" :meal="details" @edit="openEdit(details)" @remove="deleting = details" />
    </ResponsivePanel>
    <ResponsivePanel v-model:open="editOpen" :title="editing?.id ? t('meal.edit') : t('meal.add')" :description="t('meal.formBody')">
      <MealForm v-if="editing" :meal="editing" @save="save" @cancel="editOpen = false" />
    </ResponsivePanel>
    <ConfirmDialog
      v-model:open="deleteOpen"
      :title="t('meal.deleteTitle')"
      :description="t('meal.deleteBody')"
      :confirm-label="t('common.delete')"
      @confirm="confirmDelete"
    />
  </div>
</template>
