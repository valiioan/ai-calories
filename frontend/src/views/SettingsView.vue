<script setup lang="ts">
import { reactive } from 'vue'
import { useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import ConfirmDialog from '@/components/layout/ConfirmDialog.vue'
import FormField from '@/components/forms/FormField.vue'
import { Switch } from '@/components/ui/switch'
import { useI18n } from '@/composables/useI18n'
import { useAppStore } from '@/stores/app'
import type { Appearance, Units } from '@/types/domain'
import { ref } from 'vue'
import { toast } from 'vue-sonner'

const app = useAppStore()
const router = useRouter()
const { t } = useI18n()
const deleteOpen = ref(false)
const zones = ['Europe/Bucharest', 'Europe/London', 'Europe/Paris', 'America/New_York', 'UTC']
const form = reactive({
  target: app.profile.targetWeightKg ? String(app.profile.targetWeightKg) : '',
  calories: app.profile.calorieTarget ? String(app.profile.calorieTarget) : '',
})
const saving = ref(false)

async function saveGoals() {
  saving.value = true
  try {
    await app.saveProfile({
      ...app.profile,
      targetWeightKg: form.target.trim() ? Number(form.target.replace(',', '.')) : null,
      calorieTarget: form.calories.trim() ? Number(form.calories) : null,
    })
  } finally {
    saving.value = false
  }
}

async function logout() {
  await app.logout()
  await router.push({ name: 'login' })
}

function setAppearance(value: Appearance) {
  app.setAppearance(value)
}

function setUnits(value: Units) {
  app.setUnits(value)
}

function setLocale(value: unknown) {
  if (value === 'ro' || value === 'en') app.setLocale(value)
}

function setTimezone(value: unknown) {
  if (typeof value === 'string') void app.setTimezone(value)
}
</script>

<template>
  <div class="grid gap-4">
    <h1 class="text-3xl font-semibold">{{ t('settings.title') }}</h1>

    <Card>
      <CardHeader><CardTitle>{{ t('settings.appearance') }}</CardTitle></CardHeader>
      <CardContent class="flex flex-wrap gap-2">
        <Button type="button" class="h-11" :variant="app.appearance === 'light' ? 'default' : 'outline'" :aria-pressed="app.appearance === 'light'" @click="setAppearance('light')">{{ t('settings.light') }}</Button>
        <Button type="button" class="h-11" :variant="app.appearance === 'dark' ? 'default' : 'outline'" :aria-pressed="app.appearance === 'dark'" @click="setAppearance('dark')">{{ t('settings.dark') }}</Button>
        <Button type="button" class="h-11" :variant="app.appearance === 'system' ? 'default' : 'outline'" :aria-pressed="app.appearance === 'system'" @click="setAppearance('system')">{{ t('settings.system') }}</Button>
      </CardContent>
    </Card>

    <Card>
      <CardHeader><CardTitle>{{ t('settings.language') }}</CardTitle></CardHeader>
      <CardContent>
        <Select :model-value="app.locale" @update:model-value="setLocale">
          <SelectTrigger id="language" class="h-11 w-full" :aria-label="t('settings.language')">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="ro">{{ t('settings.romanian') }}</SelectItem>
            <SelectItem value="en">{{ t('settings.english') }}</SelectItem>
          </SelectContent>
        </Select>
      </CardContent>
    </Card>

    <Card>
      <CardHeader><CardTitle>{{ t('settings.units') }}</CardTitle></CardHeader>
      <CardContent class="flex flex-wrap gap-2">
        <Button type="button" class="h-11" :variant="app.profile.units === 'metric' ? 'default' : 'outline'" :aria-pressed="app.profile.units === 'metric'" @click="setUnits('metric')">{{ t('onboarding.metric') }}</Button>
        <Button type="button" class="h-11" :variant="app.profile.units === 'imperial' ? 'default' : 'outline'" :aria-pressed="app.profile.units === 'imperial'" @click="setUnits('imperial')">{{ t('onboarding.imperial') }}</Button>
      </CardContent>
    </Card>

    <Card>
      <CardHeader><CardTitle>{{ t('settings.timezone') }}</CardTitle></CardHeader>
      <CardContent>
        <Select :model-value="app.profile.timezone" @update:model-value="setTimezone">
          <SelectTrigger id="timezone" class="h-11 w-full" :aria-label="t('settings.timezone')">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="zone in zones" :key="zone" :value="zone">{{ zone }}</SelectItem>
          </SelectContent>
        </Select>
      </CardContent>
    </Card>

    <Card>
      <CardHeader>
        <CardTitle>{{ t('settings.notifications') }}</CardTitle>
        <CardDescription>{{ t('settings.notifyNote') }}</CardDescription>
      </CardHeader>
      <CardContent class="grid gap-3">
        <label class="flex min-h-11 items-center justify-between gap-3">
          <span>{{ t('settings.notifyMeals') }}</span>
          <Switch :model-value="app.notifications.meals" @update:model-value="(value) => app.setNotification('meals', value === true)" />
        </label>
        <label class="flex min-h-11 items-center justify-between gap-3">
          <span>{{ t('settings.notifyWeight') }}</span>
          <Switch :model-value="app.notifications.weight" @update:model-value="(value) => app.setNotification('weight', value === true)" />
        </label>
        <label class="flex min-h-11 items-center justify-between gap-3">
          <span>{{ t('settings.notifyWeekly') }}</span>
          <Switch :model-value="app.notifications.weekly" @update:model-value="(value) => app.setNotification('weekly', value === true)" />
        </label>
      </CardContent>
    </Card>

    <Card>
      <CardHeader><CardTitle>{{ t('settings.privacy') }}</CardTitle></CardHeader>
      <CardContent><p class="text-sm text-muted-foreground">{{ t('settings.privacyBody') }}</p></CardContent>
    </Card>

    <Card>
      <CardHeader>
        <CardTitle>{{ t('profile.goals') }}</CardTitle>
      </CardHeader>
      <CardContent>
        <form class="grid gap-3" @submit.prevent="saveGoals">
          <FormField id="set-calories" :label="t('onboarding.calories')" :optional="t('common.optional')">
            <Input id="set-calories" v-model="form.calories" inputmode="numeric" class="h-11" />
          </FormField>
          <FormField id="set-target" :label="t('onboarding.target')" :optional="t('common.optional')">
            <Input id="set-target" v-model="form.target" inputmode="decimal" class="h-11" />
          </FormField>
          <Button type="submit" class="h-11" :disabled="saving">{{ t('common.save') }}</Button>
        </form>
      </CardContent>
    </Card>

    <Card>
      <CardHeader>
        <CardTitle>{{ t('settings.delete') }}</CardTitle>
        <CardDescription>{{ t('settings.deleteBody') }}</CardDescription>
      </CardHeader>
      <CardContent>
        <Button type="button" variant="destructive" class="h-11" @click="deleteOpen = true">{{ t('settings.delete') }}</Button>
      </CardContent>
    </Card>

    <Button type="button" variant="outline" class="h-11" @click="logout">{{ t('settings.logout') }}</Button>
    <ConfirmDialog v-model:open="deleteOpen" :title="t('settings.delete')" :description="t('settings.deleteBody')" :confirm-label="t('settings.deleteConfirm')" @confirm="toast.success(t('settings.deleteDone'))" />
  </div>
</template>
