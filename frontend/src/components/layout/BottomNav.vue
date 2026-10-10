<script setup lang="ts">
import { BookOpen, ChartColumn, Home, Plus } from '@lucide/vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from '@/composables/useI18n'
import { useAppStore } from '@/stores/app'
import { cn } from '@/lib/utils'

const route = useRoute()
const app = useAppStore()
const { t } = useI18n()

const items = [
  { name: 'dashboard', to: '/', icon: Home, label: 'nav.home' as const },
  { name: 'journal', to: '/jurnal', icon: BookOpen, label: 'nav.journal' as const },
  { name: 'chat', to: '/chat', icon: Plus, label: 'nav.chat' as const },
  { name: 'progress', to: '/progres', icon: ChartColumn, label: 'nav.progress' as const },
]
</script>

<template>
  <nav
    v-show="!(route.name === 'chat' && app.composerFocused)"
    class="fixed inset-x-0 bottom-0 z-40 border-t border-border bg-card pb-[env(safe-area-inset-bottom)] md:hidden"
    :aria-label="t('nav.home')"
  >
    <ul class="grid grid-cols-4">
      <li v-for="item in items" :key="item.name">
        <RouterLink
          :to="item.to"
          class="flex min-h-16 flex-col items-center justify-center gap-1 text-xs"
          :class="cn(route.name === item.name ? 'text-primary' : 'text-muted-foreground')"
          :aria-current="route.name === item.name ? 'page' : undefined"
        >
          <span
            :class="cn(
              'grid place-items-center',
              item.name === 'chat' ? 'size-12 -mt-5 rounded-full bg-primary text-primary-foreground' : 'size-6',
              route.name === item.name && item.name !== 'chat' && 'text-primary',
            )"
          >
            <component :is="item.icon" />
          </span>
          <span :class="cn(route.name === item.name && 'font-semibold')">{{ t(item.label) }}</span>
        </RouterLink>
      </li>
    </ul>
  </nav>
</template>
