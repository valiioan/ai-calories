<script setup lang="ts">
import ChatPanel from '@/components/chat/ChatPanel.vue'
import { useAppStore } from '@/stores/app'
import { cn } from '@/lib/utils'

const app = useAppStore()
</script>

<template>
  <div
    :class="cn(
      'fixed inset-0 z-30 flex bg-background md:static md:inset-auto md:z-auto md:block md:h-[calc(100svh-8rem)]',
      app.composerFocused ? 'pb-0' : 'pb-20 md:pb-0',
    )"
  >
    <ChatPanel layout="page" class="min-h-0 flex-1" />
  </div>
</template>
