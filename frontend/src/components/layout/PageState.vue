<script setup lang="ts">
import { TriangleAlert } from '@lucide/vue'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import { useI18n } from '@/composables/useI18n'
import { useAppStore } from '@/stores/app'

const { t } = useI18n()
const app = useAppStore()
</script>

<template>
  <div v-if="app.viewStatus === 'loading'" class="grid gap-3" aria-busy="true">
    <span class="sr-only">{{ t('state.loading') }}</span>
    <Skeleton class="h-8 w-40" />
    <Skeleton class="h-36 w-full" />
    <Skeleton class="h-24 w-full" />
  </div>
  <div v-else-if="app.viewStatus === 'error'" class="grid gap-3">
    <Alert variant="destructive">
      <TriangleAlert />
      <AlertTitle>{{ t('state.errorTitle') }}</AlertTitle>
      <AlertDescription>{{ t('state.errorBody') }}</AlertDescription>
    </Alert>
    <Button type="button" class="h-11 w-full sm:w-fit" @click="app.retryView()">
      {{ t('state.retry') }}
    </Button>
  </div>
  <slot v-else />
</template>
