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

const email = ref('')
const password = ref('')
const errors = ref<Record<string, string>>({})
const formError = ref('')
const loading = ref(false)
const router = useRouter()
const app = useAppStore()
const { t } = useI18n()

function validEmail(value: string) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)
}

async function submit() {
  errors.value = {}
  formError.value = ''
  if (!validEmail(email.value)) errors.value.email = t('auth.invalidEmail')
  if (password.value.length < 8) errors.value.password = t('auth.shortPassword')
  if (Object.keys(errors.value).length) return
  loading.value = true
  try {
    await app.login(email.value.trim(), password.value)
    await router.push({ name: 'dashboard' })
  } catch (error) {
    formError.value = error instanceof ApiError && error.status === 429
      ? t('auth.throttled')
      : t('auth.errorCredentials')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <AuthLayout>
    <form class="grid gap-4" novalidate @submit.prevent="submit">
      <div class="grid gap-1">
        <h1 class="text-2xl font-semibold">{{ t('auth.loginTitle') }}</h1>
        <p class="text-sm text-muted-foreground">{{ t('auth.loginBody') }}</p>
      </div>
      <Alert v-if="formError" variant="destructive">
        <TriangleAlert />
        <AlertTitle>{{ t('state.errorTitle') }}</AlertTitle>
        <AlertDescription>{{ formError }}</AlertDescription>
      </Alert>
      <FormField id="email" :label="t('auth.email')" :error="errors.email">
        <Input id="email" v-model="email" type="email" autocomplete="email" class="h-11" :aria-invalid="!!errors.email" :aria-describedby="errors.email ? 'email-error' : undefined" />
      </FormField>
      <FormField id="password" :label="t('auth.password')" :error="errors.password">
        <PasswordField id="password" v-model="password" autocomplete="current-password" :invalid="!!errors.password" />
      </FormField>
      <Button type="submit" class="h-11 w-full hover:bg-primary-hover" :disabled="loading">
        <Spinner v-if="loading" data-icon="inline-start" />
        {{ t('auth.submitLogin') }}
      </Button>
      <p class="text-sm text-muted-foreground">{{ t('auth.demoHint') }}</p>
      <p class="text-sm">
        <RouterLink to="/forgot-password" class="text-primary underline-offset-4 hover:underline">{{ t('auth.forgotLink') }}</RouterLink>
      </p>
      <p class="text-sm">
        {{ t('auth.noAccount') }}
        <RouterLink to="/register" class="text-primary underline-offset-4 hover:underline">{{ t('auth.create') }}</RouterLink>
      </p>
    </form>
  </AuthLayout>
</template>
