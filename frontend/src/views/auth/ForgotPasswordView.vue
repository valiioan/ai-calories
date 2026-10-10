<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { CircleCheck } from '@lucide/vue'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Spinner } from '@/components/ui/spinner'
import AuthLayout from '@/components/layout/AuthLayout.vue'
import FormField from '@/components/forms/FormField.vue'
import { useI18n } from '@/composables/useI18n'
import { ApiError, apiJson, ensureCsrf } from '@/lib/api'

const email = ref('')
const error = ref('')
const sent = ref(false)
const resetUrl = ref('')
const loading = ref(false)
const { t } = useI18n()

async function submit() {
  error.value = ''
  sent.value = false
  resetUrl.value = ''
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
    error.value = t('auth.invalidEmail')
    return
  }
  loading.value = true
  try {
    await ensureCsrf()
    const result = await apiJson<{ reset_url?: string }>('/forgot-password', {
      method: 'POST',
      body: JSON.stringify({ email: email.value.trim() }),
    })
    sent.value = true
    resetUrl.value = result.reset_url ?? ''
  } catch (reason) {
    error.value = reason instanceof ApiError && reason.errors.email?.[0]
      ? t('auth.throttled')
      : t('account.saveFailed')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <AuthLayout>
    <form class="grid gap-4" novalidate @submit.prevent="submit">
      <div class="grid gap-1">
        <h1 class="text-2xl font-semibold">{{ t('auth.forgotTitle') }}</h1>
        <p class="text-sm text-muted-foreground">{{ t('auth.forgotBody') }}</p>
      </div>
      <Alert v-if="sent">
        <CircleCheck />
        <AlertTitle>{{ t('account.savedTitle') }}</AlertTitle>
        <AlertDescription>
          {{ t('auth.forgotSent') }}
          <a v-if="resetUrl" :href="resetUrl" class="mt-2 block text-primary underline-offset-4 hover:underline">{{ t('auth.resetOpen') }}</a>
        </AlertDescription>
      </Alert>
      <FormField id="email" :label="t('auth.email')" :error="error">
        <Input id="email" v-model="email" type="email" autocomplete="email" class="h-11" :aria-invalid="!!error" />
      </FormField>
      <Button type="submit" class="h-11 w-full hover:bg-primary-hover" :disabled="loading">
        <Spinner v-if="loading" data-icon="inline-start" />
        {{ t('auth.submitForgot') }}
      </Button>
      <p class="text-sm">
        <RouterLink to="/reset-password" class="text-primary underline-offset-4 hover:underline">{{ t('auth.resetLink') }}</RouterLink>
      </p>
      <p class="text-sm">
        <RouterLink to="/login" class="text-primary underline-offset-4 hover:underline">{{ t('auth.backLogin') }}</RouterLink>
      </p>
    </form>
  </AuthLayout>
</template>
