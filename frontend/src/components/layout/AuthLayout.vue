<script setup lang="ts">
import LogoMark from '@/components/brand/LogoMark.vue'
import { useI18n } from '@/composables/useI18n'

const { t } = useI18n()
</script>

<template>
  <div class="grid min-h-svh lg:grid-cols-2">
    <section class="hidden flex-col justify-between bg-primary p-10 text-primary-foreground lg:flex">
      <LogoMark tone="inverse" />
      <div class="grid max-w-md gap-3">
        <h2 class="text-3xl font-semibold">{{ t('auth.panelTitle') }}</h2>
        <p>{{ t('auth.panelBody') }}</p>
      </div>
      <p class="text-sm">{{ t('auth.demoHint') }}</p>
    </section>
    <section class="flex min-h-svh flex-col px-4 py-6 sm:px-8">
      <LogoMark class="lg:hidden" />
      <div class="mx-auto flex w-full max-w-md flex-1 flex-col justify-center gap-6 py-8">
        <slot />
      </div>
    </section>
  </div>
</template>
