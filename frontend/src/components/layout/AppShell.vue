<script setup lang="ts">
import { computed } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import { Sparkles } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import BottomNav from '@/components/layout/BottomNav.vue'
import DemoBanner from '@/components/layout/DemoBanner.vue'
import MobileHeader from '@/components/layout/MobileHeader.vue'
import PageState from '@/components/layout/PageState.vue'
import SideNav from '@/components/layout/SideNav.vue'
import ChatPanel from '@/components/chat/ChatPanel.vue'
import { useI18n } from '@/composables/useI18n'
import { useMediaQuery } from '@/composables/useMediaQuery'
import { useAppStore } from '@/stores/app'
import { cn } from '@/lib/utils'

const route = useRoute()
const app = useAppStore()
const { t } = useI18n()
const tablet = useMediaQuery('(min-width: 768px)')
const desktop = useMediaQuery('(min-width: 1280px)')
const laptopL = useMediaQuery('(min-width: 1441px)')

const showDock = computed(() => desktop.value && app.aiDockOpen && route.name !== 'chat')
const frame = computed(() => cn(
  tablet.value && (desktop.value || !app.navCollapsed ? 'md:pl-60' : 'md:pl-16'),
  showDock.value && (laptopL.value ? 'xl:pr-[33.75rem]' : 'xl:pr-[22.5rem]'),
))
</script>

<template>
  <div class="min-h-svh overflow-x-hidden bg-background">
    <a href="#continut" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:m-3 focus:rounded-md focus:bg-card focus:px-3 focus:py-2">
      {{ t('nav.skip') }}
    </a>
    <SideNav />
    <div :class="frame">
      <MobileHeader class="md:hidden" />
      <DemoBanner />
      <main id="continut" class="mx-auto w-full max-w-5xl px-4 pt-2 pb-28 md:px-6 md:pt-6 md:pb-10">
        <PageState>
          <RouterView />
        </PageState>
      </main>
    </div>
    <aside
      v-if="showDock"
      class="fixed inset-y-0 right-0 z-30 hidden w-[22.5rem] flex-col border-l border-border bg-card xl:flex min-[1441px]:w-[33.75rem]"
    >
      <ChatPanel layout="dock" class="min-h-0 flex-1" />
    </aside>
    <Button
      v-if="desktop && !app.aiDockOpen && route.name !== 'chat'"
      type="button"
      class="fixed right-4 bottom-4 z-30 hidden size-12 xl:inline-flex"
      :aria-label="t('chat.expand')"
      @click="app.aiDockOpen = true"
    >
      <Sparkles />
    </Button>
    <BottomNav />
  </div>
</template>
