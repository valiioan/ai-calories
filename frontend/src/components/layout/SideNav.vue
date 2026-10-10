<script setup lang="ts">
import { BookOpen, ChartColumn, Dumbbell, Home, LogOut, PanelLeft, Scale, Settings, Sparkles, UserRound } from '@lucide/vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import LogoMark from '@/components/brand/LogoMark.vue'
import { useI18n } from '@/composables/useI18n'
import { useMediaQuery } from '@/composables/useMediaQuery'
import { useAppStore } from '@/stores/app'
import { cn } from '@/lib/utils'

const route = useRoute()
const router = useRouter()
const app = useAppStore()
const { t } = useI18n()
const desktop = useMediaQuery('(min-width: 1280px)')

const main = [
  { name: 'dashboard', to: '/', icon: Home, label: 'nav.dashboard' as const },
  { name: 'journal', to: '/jurnal', icon: BookOpen, label: 'nav.journal' as const },
  { name: 'activities', to: '/activitati', icon: Dumbbell, label: 'nav.activities' as const },
  { name: 'weight', to: '/greutate', icon: Scale, label: 'nav.weight' as const },
  { name: 'progress', to: '/progres', icon: ChartColumn, label: 'nav.reports' as const },
]

const secondary = [
  { name: 'settings', to: '/setari', icon: Settings, label: 'nav.settings' as const },
  { name: 'profile', to: '/profil', icon: UserRound, label: 'nav.profile' as const },
]

async function logout() {
  await app.logout()
  await router.push({ name: 'login' })
}
</script>

<template>
  <aside
    class="fixed inset-y-0 left-0 z-40 hidden flex-col border-r border-border bg-card md:flex"
    :class="desktop || !app.navCollapsed ? 'w-60' : 'w-16'"
  >
    <div class="flex h-16 items-center gap-2 px-3">
      <LogoMark :compact="!desktop && app.navCollapsed" />
    </div>
    <nav class="flex flex-1 flex-col gap-1 px-2" :aria-label="t('nav.dashboard')">
      <RouterLink
        to="/chat"
        :title="t('nav.chat')"
        :aria-current="route.name === 'chat' ? 'page' : undefined"
        :class="cn(
          'flex min-h-11 items-center gap-3 rounded-lg px-3 text-sm xl:hidden',
          route.name === 'chat' ? 'bg-secondary font-semibold text-secondary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground',
        )"
      >
        <Sparkles />
        <span v-if="!app.navCollapsed" class="truncate">{{ t('nav.chat') }}</span>
        <span v-else class="sr-only">{{ t('nav.chat') }}</span>
      </RouterLink>
      <RouterLink
        v-for="item in main"
        :key="item.name"
        :to="item.to"
        :title="t(item.label)"
        :aria-current="route.name === item.name ? 'page' : undefined"
        :class="cn(
          'flex min-h-11 items-center gap-3 rounded-lg px-3 text-sm',
          route.name === item.name ? 'bg-secondary font-semibold text-secondary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground',
        )"
      >
        <component :is="item.icon" />
        <span v-if="desktop || !app.navCollapsed" class="truncate">{{ t(item.label) }}</span>
        <span v-else class="sr-only">{{ t(item.label) }}</span>
      </RouterLink>
      <div class="mt-auto grid gap-1 pb-3">
        <RouterLink
          v-for="item in secondary"
          :key="item.name"
          :to="item.to"
          :title="t(item.label)"
          :aria-current="route.name === item.name ? 'page' : undefined"
          :class="cn(
            'flex min-h-11 items-center gap-3 rounded-lg px-3 text-sm',
            route.name === item.name ? 'bg-secondary font-semibold text-secondary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground',
          )"
        >
          <component :is="item.icon" />
          <span v-if="desktop || !app.navCollapsed" class="truncate">{{ t(item.label) }}</span>
          <span v-else class="sr-only">{{ t(item.label) }}</span>
        </RouterLink>
        <button
          type="button"
          class="flex min-h-11 items-center gap-3 rounded-lg px-3 text-left text-sm text-muted-foreground hover:bg-muted hover:text-foreground"
          @click="logout"
        >
          <LogOut />
          <span v-if="desktop || !app.navCollapsed">{{ t('nav.logout') }}</span>
          <span v-else class="sr-only">{{ t('nav.logout') }}</span>
        </button>
        <Button
          type="button"
          variant="ghost"
          class="h-11 xl:hidden"
          :aria-label="app.navCollapsed ? t('nav.expand') : t('nav.collapse')"
          @click="app.navCollapsed = !app.navCollapsed"
        >
          <PanelLeft data-icon="inline-start" />
          <span v-if="!app.navCollapsed">{{ t('nav.collapse') }}</span>
        </Button>
      </div>
    </nav>
  </aside>
</template>
