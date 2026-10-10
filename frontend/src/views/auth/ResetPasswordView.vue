<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { CircleCheck } from '@lucide/vue'
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

const route = useRoute()
const router = useRouter()
const app = useAppStore()
const password = ref('')
const confirm = ref('')
const email = ref(typeof route.query.email === 'string' ? route.query.email : '')
const token = typeof route.query.token === 'string' ? route.query.token : ''
const errors = ref<Record<string, string>>({})
const formError = ref('')
const done = ref(false)
const loading = ref(false)
const { t } = useI18n()

async function submit() {
  errors.value = {}
  formError.value = ''
  done.value = false
  if (!token) {
    formError.value = t('auth.resetMissing')
    return
  }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) errors.value.email = t('auth.invalidEmail')
  if (password.value.length < 8) errors.value.password = t('auth.shortPassword')
  if (password.value !== confirm.value) errors.value.confirm = t('auth.mismatch')
  if (Object.keys(errors.value).length) return
  loading.value = true
  try {
    await app.resetPassword(email.value.trim(), token, password.value, confirm.value)
    done.value = true
    password.value = ''
    confirm.value = ''
  } catch (error) {
    formError.value = error instanceof ApiError ? t('auth.resetInvalid') : t('account.saveFailed')
  } finally {
    loading.value = false
  }
}

function continueIn() {
  void router.push({ name: 'dashboard' })
}
</script>

<template>
  <AuthLayout>
    <form class="grid gap-4" novalidate @submit.prevent="submit">
      <div class="grid gap-1">
        <h1 class="text-2xl font-semibold">{{ t('auth.resetTitle') }}</h1>
        <p class="text-sm text-muted-foreground">{{ t('auth.resetBody') }}</p>
      </div>
      <Alert v-if="!token" variant="destructive">
        <AlertTitle>{{ t('state.errorTitle') }}</AlertTitle>
        <AlertDescription>{{ t('auth.resetMissing') }}</AlertDescription>
      </Alert>
      <Alert v-if="formError" variant="destructive">
        <AlertTitle>{{ t('state.errorTitle') }}</AlertTitle>
        <AlertDescription>{{ formError }}</AlertDescription>
      </Alert>
      <Alert v-if="done">
        <CircleCheck />
        <AlertTitle>{{ t('account.savedTitle') }}</AlertTitle>
        <AlertDescription>{{ t('auth.resetDone') }}</AlertDescription>
      </Alert>
      <FormField id="email" :label="t('auth.email')" :error="errors.email">
        <Input id="email" v-model="email" type="email" autocomplete="email" class="h-11" :aria-invalid="!!errors.email" />
      </FormField>
      <FormField id="password" :label="t('auth.password')" :error="errors.password">
        <PasswordField id="password" v-model="password" autocomplete="new-password" :invalid="!!errors.password" />
      </FormField>
      <FormField id="confirm" :label="t('auth.confirm')" :error="errors.confirm">
        <PasswordField id="confirm" v-model="confirm" autocomplete="new-password" :invalid="!!errors.confirm" />
      </FormField>
      <Button v-if="done" type="button" class="h-11 w-full hover:bg-primary-hover" @click="continueIn">{{ t('auth.resetContinue') }}</Button>
      <Button v-else type="submit" class="h-11 w-full hover:bg-primary-hover" :disabled="loading || !token">
        <Spinner v-if="loading" data-icon="inline-start" />
        {{ t('auth.submitReset') }}
      </Button>
      <p class="text-sm">
        <RouterLink to="/login" class="text-primary underline-offset-4 hover:underline">{{ t('auth.backLogin') }}</RouterLink>
      </p>
    </form>
  </AuthLayout>
</template>
