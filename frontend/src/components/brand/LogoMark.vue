<script setup lang="ts">
import { Sparkles } from '@lucide/vue'
import { appName } from '@/lib/appName'
import { cn } from '@/lib/utils'

const props = withDefaults(defineProps<{ tone?: 'default' | 'inverse'; compact?: boolean }>(), {
  tone: 'default',
  compact: false,
})
</script>

<template>
  <span :class="cn('inline-flex items-center gap-2', props.tone === 'inverse' && 'text-primary-foreground')">
    <span
      :class="cn(
        'grid size-9 place-items-center rounded-xl',
        props.tone === 'inverse' ? 'bg-primary-foreground text-primary' : 'bg-primary text-primary-foreground',
      )"
    >
      <Sparkles />
    </span>
    <span v-if="!props.compact" class="text-lg font-semibold tracking-tight">{{ appName }}</span>
  </span>
</template>
