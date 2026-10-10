<script setup lang="ts">
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/components/ui/alert-dialog'
import { useI18n } from '@/composables/useI18n'

const open = defineModel<boolean>('open', { required: true })
defineProps<{ title: string; description: string; confirmLabel: string }>()
const emit = defineEmits<{ confirm: [] }>()
const { t } = useI18n()
</script>

<template>
  <AlertDialog v-model:open="open">
    <AlertDialogContent>
      <AlertDialogHeader>
        <AlertDialogTitle>{{ title }}</AlertDialogTitle>
        <AlertDialogDescription>{{ description }}</AlertDialogDescription>
      </AlertDialogHeader>
      <AlertDialogFooter>
        <AlertDialogCancel class="h-11">{{ t('common.cancel') }}</AlertDialogCancel>
        <AlertDialogAction class="h-11" variant="destructive" @click="emit('confirm')">
          {{ confirmLabel }}
        </AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>
