<script setup lang="ts">
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/components/ui/sheet'
import { useMediaQuery } from '@/composables/useMediaQuery'

const open = defineModel<boolean>('open', { required: true })
defineProps<{ title: string; description: string }>()
const desktop = useMediaQuery('(min-width: 1280px)')
</script>

<template>
  <Dialog v-if="desktop" v-model:open="open">
    <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-lg">
      <DialogHeader>
        <DialogTitle>{{ title }}</DialogTitle>
        <DialogDescription>{{ description }}</DialogDescription>
      </DialogHeader>
      <slot />
    </DialogContent>
  </Dialog>
  <Sheet v-else v-model:open="open">
    <SheetContent side="bottom" class="max-h-[92vh] overflow-y-auto rounded-t-2xl">
      <SheetHeader>
        <SheetTitle>{{ title }}</SheetTitle>
        <SheetDescription>{{ description }}</SheetDescription>
      </SheetHeader>
      <slot />
    </SheetContent>
  </Sheet>
</template>
