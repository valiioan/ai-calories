<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { TriangleAlert } from '@lucide/vue'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Spinner } from '@/components/ui/spinner'
import AuthLayout from '@/components/layout/AuthLayout.vue'
import FormField from '@/components/forms/FormField.vue'
import PasswordField from '@/components/forms/PasswordField.vue'
import { useI18n } from '@/composables/useI18n'
import { ApiError } from '@/lib/api'
import { useAppStore } from '@/stores/app'

const lastName = ref('')
const firstName = ref('')
const email = ref('')
const password = ref('')
const confirm = ref('')
const errors = ref<Record<string, string>>({})
const formError = ref('')
const loading = ref(false)
const router = useRouter()
const app = useAppStore()
const { t } = useI18n()

async function submit() {
  errors.value = {}
  if (!lastName.value.trim()) errors.value.lastName = t('auth.lastNameRequired')
  if (!firstName.value.trim()) errors.value.firstName = t('auth.firstNameRequired')
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) errors.value.email = t('auth.invalidEmail')
  if (password.value.length < 8) errors.value.password = t('auth.shortPassword')
  if (password.value !== confirm.value) errors.value.confirm = t('auth.mismatch')
  if (Object.keys(errors.value).length) return
  loading.value = true
  formError.value = ''
  try {
    await app.register(firstName.value.trim(), lastName.value.trim(), email.value.trim(), password.value, confirm.value)
    await router.push({ name: 'onboarding' })
  } catch (error) {
    if (error instanceof ApiError && error.errors.email) errors.value.email = t('auth.emailTaken')
    else if (error instanceof ApiError && error.errors.last_name) errors.value.lastName = t('auth.lastNameRequired')
    else if (error instanceof ApiError && error.errors.first_name) errors.value.firstName = t('auth.firstNameRequired')
    else formError.value = t('account.saveFailed')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <AuthLayout>
    <form class="grid gap-4" novalidate @submit.prevent="submit">
      <div class="grid gap-1">
        <h1 class="text-2xl font-semibold">{{ t('auth.registerTitle') }}</h1>
        <p class="text-sm text-muted-foreground">{{ t('auth.registerBody') }}</p>
      </div>
      <Alert v-if="formError" variant="destructive">
        <TriangleAlert />
        <AlertTitle>{{ t('state.errorTitle') }}</AlertTitle>
        <AlertDescription>{{ formError }}</AlertDescription>
      </Alert>
      <div class="grid grid-cols-2 gap-3">
        <FormField id="first-name" :label="t('auth.firstName')" :error="errors.firstName">
          <Input id="first-name" v-model="firstName" autocomplete="given-name" class="h-11" :aria-invalid="!!errors.firstName" />
        </FormField>
        <FormField id="last-name" :label="t('auth.lastName')" :error="errors.lastName">
          <Input id="last-name" v-model="lastName" autocomplete="family-name" class="h-11" :aria-invalid="!!errors.lastName" />
        </FormField>
      </div>
      <FormField id="email" :label="t('auth.email')" :error="errors.email">
        <Input id="email" v-model="email" type="email" autocomplete="email" class="h-11" :aria-invalid="!!errors.email" />
      </FormField>
      <FormField id="password" :label="t('auth.password')" :error="errors.password">
        <PasswordField id="password" v-model="password" autocomplete="new-password" :invalid="!!errors.password" />
      </FormField>
      <FormField id="confirm" :label="t('auth.confirm')" :error="errors.confirm">
        <PasswordField id="confirm" v-model="confirm" autocomplete="new-password" :invalid="!!errors.confirm" />
      </FormField>
      <Button type="submit" class="h-11 w-full hover:bg-primary-hover" :disabled="loading">
        <Spinner v-if="loading" data-icon="inline-start" />
        {{ t('auth.submitRegister') }}
      </Button>
      <p class="text-sm">
        {{ t('auth.hasAccount') }}
        <RouterLink to="/login" class="text-primary underline-offset-4 hover:underline">{{ t('auth.loginLink') }}</RouterLink>
      </p>
    </form>
  </AuthLayout>
</template>
