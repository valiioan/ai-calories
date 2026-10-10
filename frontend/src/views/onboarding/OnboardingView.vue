<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Progress } from '@/components/ui/progress'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Spinner } from '@/components/ui/spinner'
import LogoMark from '@/components/brand/LogoMark.vue'
import BirthDateField from '@/components/forms/BirthDateField.vue'
import FormField from '@/components/forms/FormField.vue'
import { useI18n } from '@/composables/useI18n'
import { useAppStore } from '@/stores/app'
import type { ActivityLevel, Goal, Profile, Units } from '@/types/domain'

const step = ref(1)
const router = useRouter()
const app = useAppStore()
const { t } = useI18n()
const errors = reactive<Record<string, string>>({})
const saving = ref(false)
const form = reactive({
  dob: app.profile.dob,
  height: app.profile.heightCm ? String(app.profile.heightCm) : '',
  weight: app.profile.weightKg ? String(app.profile.weightKg) : '',
  target: app.profile.targetWeightKg ? String(app.profile.targetWeightKg) : '',
  activity: app.profile.activityLevel as ActivityLevel,
  goal: app.profile.goal as Goal,
  calories: app.profile.calorieTarget ? String(app.profile.calorieTarget) : '',
  units: app.profile.units as Units,
})

const progress = computed(() => (step.value / 4) * 100)

function clearErrors() {
  for (const key of Object.keys(errors)) delete errors[key]
}

function next() {
  clearErrors()
  if (step.value === 2) {
    if (!form.dob || form.dob > '2015-01-01') errors.dob = t('onboarding.invalidDob')
    const height = Number(form.height)
    if (!Number.isFinite(height) || height < 100 || height > 250) errors.height = t('onboarding.invalidHeight')
    const weight = Number(form.weight.replace(',', '.'))
    if (!Number.isFinite(weight) || weight < 30 || weight > 300) errors.weight = t('onboarding.invalidWeight')
    if (Object.keys(errors).length) return
  }
  if (step.value === 3) {
    if (form.target.trim()) {
      const target = Number(form.target.replace(',', '.'))
      if (!Number.isFinite(target) || target < 30 || target > 300) errors.target = t('onboarding.invalidTarget')
    }
    if (form.calories.trim()) {
      const calories = Number(form.calories)
      if (!Number.isFinite(calories) || calories < 800 || calories > 6000) errors.calories = t('onboarding.invalidCalories')
    }
    if (Object.keys(errors).length) return
  }
  step.value += 1
}

async function finish() {
  const profile: Profile = {
    ...app.profile,
    dob: form.dob,
    heightCm: Number(form.height),
    weightKg: Number(form.weight.replace(',', '.')),
    targetWeightKg: form.target.trim() ? Number(form.target.replace(',', '.')) : null,
    activityLevel: form.activity,
    goal: form.goal,
    calorieTarget: form.calories.trim() ? Number(form.calories) : null,
    units: form.units,
  }
  saving.value = true
  try {
    await app.finishOnboarding(profile)
    await router.push({ name: 'dashboard' })
  } catch {
    errors.form = t('account.saveFailed')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <main class="mx-auto flex w-full max-w-lg flex-col gap-6 px-4 py-6">
    <LogoMark />
    <div class="grid gap-2">
      <p class="text-sm text-muted-foreground">{{ t('onboarding.step', { current: step, total: 4 }) }}</p>
      <Progress :model-value="progress" :aria-label="t('onboarding.step', { current: step, total: 4 })" />
    </div>

    <section v-if="step === 1" class="grid gap-3">
      <h1 class="text-2xl font-semibold">{{ t('onboarding.welcomeTitle', { name: app.profile.firstName }) }}</h1>
      <p class="text-muted-foreground">{{ t('onboarding.welcomeBody') }}</p>
      <p class="rounded-xl bg-secondary p-4 text-sm text-secondary-foreground">{{ t('onboarding.approx') }}</p>
      <Button type="button" class="h-11 hover:bg-primary-hover" @click="next">{{ t('common.continue') }}</Button>
    </section>

    <form v-else-if="step === 2" class="grid gap-4" @submit.prevent="next">
      <div>
        <h1 class="text-2xl font-semibold">{{ t('onboarding.personalTitle') }}</h1>
        <p class="text-sm text-muted-foreground">{{ t('onboarding.personalBody') }}</p>
      </div>
      <FormField id="dob" :label="t('onboarding.dob')" :error="errors.dob">
        <BirthDateField v-model="form.dob" :invalid="!!errors.dob" />
      </FormField>
      <FormField id="height" :label="t('onboarding.height')" :error="errors.height">
        <Input id="height" v-model="form.height" inputmode="decimal" class="h-11" :aria-invalid="!!errors.height" />
      </FormField>
      <FormField id="weight" :label="t('onboarding.weight')" :error="errors.weight">
        <Input id="weight" v-model="form.weight" inputmode="decimal" class="h-11" :aria-invalid="!!errors.weight" />
      </FormField>
      <div class="grid grid-cols-2 gap-2">
        <Button type="button" variant="outline" class="h-11" @click="step -= 1">{{ t('common.back') }}</Button>
        <Button type="submit" class="h-11 hover:bg-primary-hover">{{ t('common.continue') }}</Button>
      </div>
    </form>

    <form v-else-if="step === 3" class="grid gap-4" @submit.prevent="next">
      <div>
        <h1 class="text-2xl font-semibold">{{ t('onboarding.goalsTitle') }}</h1>
        <p class="text-sm text-muted-foreground">{{ t('onboarding.goalsBody') }}</p>
      </div>
      <FormField id="target" :label="t('onboarding.target')" :optional="t('common.optional')" :error="errors.target">
        <Input id="target" v-model="form.target" inputmode="decimal" class="h-11" :aria-invalid="!!errors.target" />
      </FormField>
      <FormField id="activity" :label="t('onboarding.activity')">
        <Select v-model="form.activity">
          <SelectTrigger id="activity" class="h-11 w-full"><SelectValue /></SelectTrigger>
          <SelectContent>
            <SelectItem value="sedentary">{{ t('onboarding.sedentary') }}</SelectItem>
            <SelectItem value="light">{{ t('onboarding.light') }}</SelectItem>
            <SelectItem value="moderate">{{ t('onboarding.moderate') }}</SelectItem>
            <SelectItem value="active">{{ t('onboarding.active') }}</SelectItem>
          </SelectContent>
        </Select>
      </FormField>
      <FormField id="goal" :label="t('onboarding.goal')">
        <Select v-model="form.goal">
          <SelectTrigger id="goal" class="h-11 w-full"><SelectValue /></SelectTrigger>
          <SelectContent>
            <SelectItem value="lose">{{ t('onboarding.lose') }}</SelectItem>
            <SelectItem value="maintain">{{ t('onboarding.maintain') }}</SelectItem>
            <SelectItem value="gain">{{ t('onboarding.gain') }}</SelectItem>
          </SelectContent>
        </Select>
      </FormField>
      <FormField id="calories" :label="t('onboarding.calories')" :optional="t('common.optional')" :error="errors.calories">
        <Input id="calories" v-model="form.calories" inputmode="numeric" class="h-11" :aria-invalid="!!errors.calories" />
      </FormField>
      <FormField id="units" :label="t('onboarding.units')">
        <Select v-model="form.units">
          <SelectTrigger id="units" class="h-11 w-full"><SelectValue /></SelectTrigger>
          <SelectContent>
            <SelectItem value="metric">{{ t('onboarding.metric') }}</SelectItem>
            <SelectItem value="imperial">{{ t('onboarding.imperial') }}</SelectItem>
          </SelectContent>
        </Select>
      </FormField>
      <p class="text-sm text-muted-foreground">{{ t('onboarding.approx') }}</p>
      <div class="grid grid-cols-2 gap-2">
        <Button type="button" variant="outline" class="h-11" @click="step -= 1">{{ t('common.back') }}</Button>
        <Button type="submit" class="h-11 hover:bg-primary-hover">{{ t('common.continue') }}</Button>
      </div>
    </form>

    <section v-else class="grid gap-3 text-sm">
      <h1 class="text-2xl font-semibold">{{ t('onboarding.confirmTitle') }}</h1>
      <p class="text-muted-foreground">{{ t('onboarding.confirmBody') }}</p>
      <dl class="grid gap-2 rounded-xl border border-border bg-card p-4">
        <div class="flex justify-between gap-3"><dt>{{ t('onboarding.fullName') }}</dt><dd>{{ app.profile.firstName }} {{ app.profile.lastName }}</dd></div>
        <div class="flex justify-between gap-3"><dt>{{ t('onboarding.dob') }}</dt><dd>{{ form.dob }}</dd></div>
        <div class="flex justify-between gap-3"><dt>{{ t('onboarding.height') }}</dt><dd>{{ form.height }} cm</dd></div>
        <div class="flex justify-between gap-3"><dt>{{ t('onboarding.weight') }}</dt><dd>{{ form.weight }} kg</dd></div>
        <div class="flex justify-between gap-3"><dt>{{ t('onboarding.target') }}</dt><dd>{{ form.target || '—' }}</dd></div>
        <div class="flex justify-between gap-3"><dt>{{ t('onboarding.goal') }}</dt><dd>{{ t(`onboarding.${form.goal}`) }}</dd></div>
        <div class="flex justify-between gap-3"><dt>{{ t('onboarding.calories') }}</dt><dd>{{ form.calories || '—' }}</dd></div>
      </dl>
      <p class="rounded-xl bg-secondary p-4 text-secondary-foreground">{{ t('onboarding.approx') }}</p>
      <p v-if="errors.form" class="text-sm text-destructive">{{ errors.form }}</p>
      <div class="grid grid-cols-2 gap-2">
        <Button type="button" variant="outline" class="h-11" :disabled="saving" @click="step -= 1">{{ t('common.back') }}</Button>
        <Button type="button" class="h-11 hover:bg-primary-hover" :disabled="saving" @click="finish">
          <Spinner v-if="saving" data-icon="inline-start" />
          {{ t('common.finish') }}
        </Button>
      </div>
    </section>
  </main>
</template>
