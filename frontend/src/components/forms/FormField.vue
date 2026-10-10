<script setup lang="ts">
import { Label } from '@/components/ui/label'

defineProps<{
  id: string
  label: string
  error?: string
  optional?: string
}>()
</script>

<template>
  <div class="grid gap-1.5" :data-invalid="error ? '' : undefined">
    <Label :for="id">
      {{ label }}
      <span v-if="optional" class="font-normal text-muted-foreground"> ({{ optional }})</span>
    </Label>
    <slot />
    <p v-if="error" :id="`${id}-error`" class="text-sm text-destructive">{{ error }}</p>
  </div>
</template>
