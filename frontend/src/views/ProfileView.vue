<script setup lang="ts">
import { reactive, ref } from 'vue'
import { TriangleAlert } from '@lucide/vue'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Spinner } from '@/components/ui/spinner'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import BirthDateField from '@/components/forms/BirthDateField.vue'
import FormField from '@/components/forms/FormField.vue'
import { useI18n } from '@/composables/useI18n'
import { ApiError } from '@/lib/api'
import { useAppStore } from '@/stores/app'

const app = useAppStore()
const { t } = useI18n()
const form = reactive({
  lastName: app.profile.lastName,
  firstName: app.profile.firstName,
  email: app.profile.email,
  dob: app.profile.dob,
  heightCm: app.profile.heightCm == null ? '' : String(app.profile.heightCm),
  weightKg: String(app.profile.weightKg),
  target: app.profile.targetWeightKg ? String(app.profile.targetWeightKg) : '',
  calories: app.profile.calorieTarget ? String(app.profile.calorieTarget) : '',
  activityLevel: app.profile.activityLevel,
  goal: app.profile.goal,
})
const errors = reactive<Record<string, string>>({})
const formError = ref('')
const saving = ref(false)

async function save() {
  for (const key of Object.keys(errors)) delete errors[key]
  formError.value = ''
  if (!form.lastName.trim()) errors.lastName = t('auth.lastNameRequired')
  if (!form.firstName.trim()) errors.firstName = t('auth.firstNameRequired')
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) errors.email = t('auth.invalidEmail')
  const height = Number(form.heightCm)
  const weight = Number(form.weightKg.replace(',', '.'))
  if (!Number.isFinite(height) || height < 100 || height > 250) errors.height = t('onboarding.invalidHeight')
  if (!Number.isFinite(weight) || weight < 30 || weight > 300) errors.weight = t('onboarding.invalidWeight')
  if (form.target.trim()) {
    const target = Number(form.target.replace(',', '.'))
    if (!Number.isFinite(target) || target < 30 || target > 300) errors.target = t('onboarding.invalidTarget')
  }
  if (form.calories.trim()) {
    const calories = Number(form.calories)
    if (!Number.isFinite(calories) || calories < 800 || calories > 6000) errors.calories = t('onboarding.invalidCalories')
  }
  if (Object.keys(errors).length) return
  saving.value = true
  try {
    await app.saveProfile({
      ...app.profile,
      lastName: form.lastName.trim(),
      firstName: form.firstName.trim(),
      email: form.email.trim(),
      dob: form.dob,
      heightCm: height,
      weightKg: weight,
      targetWeightKg: form.target.trim() ? Number(form.target.replace(',', '.')) : null,
      calorieTarget: form.calories.trim() ? Number(form.calories) : null,
      activityLevel: form.activityLevel,
      goal: form.goal,
    })
  } catch (error) {
    if (error instanceof ApiError) {
      if (error.errors.email) errors.email = t('auth.emailTaken')
      if (error.errors.last_name) errors.lastName = t('auth.lastNameRequired')
      if (error.errors.first_name) errors.firstName = t('auth.firstNameRequired')
      if (error.errors.height_cm) errors.height = t('onboarding.invalidHeight')
      if (error.errors.birth_date) errors.dob = t('onboarding.invalidDob')
      if (error.errors.calorie_target) errors.calories = t('onboarding.invalidCalories')
    }
    formError.value = t('account.saveFailed')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="grid gap-4">
    <h1 class="text-3xl font-semibold">{{ t('profile.title') }}</h1>
    <form class="grid gap-4" @submit.prevent="save">
      <Alert v-if="formError" variant="destructive">
        <TriangleAlert />
        <AlertTitle>{{ t('state.errorTitle') }}</AlertTitle>
        <AlertDescription>{{ formError }}</AlertDescription>
      </Alert>
      <Card>
        <CardHeader><CardTitle>{{ t('profile.account') }}</CardTitle></CardHeader>
        <CardContent class="grid gap-3">
          <div class="grid grid-cols-2 gap-3">
            <FormField id="profile-first-name" :label="t('auth.firstName')" :error="errors.firstName">
              <Input id="profile-first-name" v-model="form.firstName" autocomplete="given-name" class="h-11" :aria-invalid="!!errors.firstName" />
            </FormField>
            <FormField id="profile-last-name" :label="t('auth.lastName')" :error="errors.lastName">
              <Input id="profile-last-name" v-model="form.lastName" autocomplete="family-name" class="h-11" :aria-invalid="!!errors.lastName" />
            </FormField>
          </div>
          <FormField id="profile-email" :label="t('profile.email')" :error="errors.email">
            <Input id="profile-email" v-model="form.email" type="email" class="h-11" :aria-invalid="!!errors.email" />
          </FormField>
        </CardContent>
      </Card>
      <Card>
        <CardHeader><CardTitle>{{ t('profile.personal') }}</CardTitle></CardHeader>
        <CardContent class="grid gap-3">
          <FormField id="profile-dob" :label="t('onboarding.dob')" :error="errors.dob">
            <BirthDateField id="profile-dob" v-model="form.dob" :invalid="!!errors.dob" />
          </FormField>
          <FormField id="profile-height" :label="t('onboarding.height')" :error="errors.height">
            <Input id="profile-height" v-model="form.heightCm" inputmode="decimal" class="h-11" :aria-invalid="!!errors.height" />
          </FormField>
          <FormField id="profile-weight" :label="t('onboarding.weight')" :error="errors.weight">
            <Input id="profile-weight" v-model="form.weightKg" inputmode="decimal" class="h-11" :aria-invalid="!!errors.weight" />
          </FormField>
        </CardContent>
      </Card>
      <Card>
        <CardHeader><CardTitle>{{ t('profile.goals') }}</CardTitle></CardHeader>
        <CardContent class="grid gap-3">
          <FormField id="profile-activity" :label="t('onboarding.activity')">
            <Select v-model="form.activityLevel">
              <SelectTrigger id="profile-activity" class="h-11 w-full"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="sedentary">{{ t('onboarding.sedentary') }}</SelectItem>
                <SelectItem value="light">{{ t('onboarding.light') }}</SelectItem>
                <SelectItem value="moderate">{{ t('onboarding.moderate') }}</SelectItem>
                <SelectItem value="active">{{ t('onboarding.active') }}</SelectItem>
              </SelectContent>
            </Select>
          </FormField>
          <FormField id="profile-goal" :label="t('onboarding.goal')">
            <Select v-model="form.goal">
              <SelectTrigger id="profile-goal" class="h-11 w-full"><SelectValue /></SelectTrigger>
              <SelectContent>
                <SelectItem value="lose">{{ t('onboarding.lose') }}</SelectItem>
                <SelectItem value="maintain">{{ t('onboarding.maintain') }}</SelectItem>
                <SelectItem value="gain">{{ t('onboarding.gain') }}</SelectItem>
              </SelectContent>
            </Select>
          </FormField>
          <FormField id="profile-target" :label="t('onboarding.target')" :optional="t('common.optional')" :error="errors.target">
            <Input id="profile-target" v-model="form.target" inputmode="decimal" class="h-11" :aria-invalid="!!errors.target" />
          </FormField>
          <FormField id="profile-calories" :label="t('onboarding.calories')" :optional="t('common.optional')" :error="errors.calories">
            <Input id="profile-calories" v-model="form.calories" inputmode="numeric" class="h-11" :aria-invalid="!!errors.calories" />
          </FormField>
          <p class="text-sm text-muted-foreground">{{ t('onboarding.approx') }}</p>
        </CardContent>
      </Card>
      <Button type="submit" class="h-11 hover:bg-primary-hover" :disabled="saving">
        <Spinner v-if="saving" data-icon="inline-start" />
        {{ t('common.save') }}
      </Button>
    </form>
  </div>
</template>
