<script setup lang="ts">
import { Info } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { useI18n } from '@/composables/useI18n'
import { useAppStore } from '@/stores/app'

const { t } = useI18n()
const app = useAppStore()
</script>

<template>
  <div class="flex flex-col gap-2 border-b border-border bg-secondary px-4 py-2 text-sm text-secondary-foreground sm:flex-row sm:items-center">
    <p class="flex items-start gap-2">
      <Info class="mt-0.5 size-4 shrink-0" />
      <span>{{ t('demo.banner') }}</span>
    </p>
    <div class="flex flex-wrap gap-2">
    <Button type="button" variant="outline" class="h-11 bg-card" @click="app.retryView()">
      {{ t('demo.showLoading') }}
    </Button>
    <Button type="button" variant="outline" class="h-11 bg-card" @click="app.showError()">
      {{ t('demo.showError') }}
    </Button>
    </div>
  </div>
</template>
