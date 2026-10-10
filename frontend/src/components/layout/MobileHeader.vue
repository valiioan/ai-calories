<script setup lang="ts">
import { Dumbbell, LogOut, Scale, Settings, UserRound } from '@lucide/vue'
import { useRouter } from 'vue-router'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu'
import LogoMark from '@/components/brand/LogoMark.vue'
import { useI18n } from '@/composables/useI18n'
import { appName } from '@/lib/appName'
import { initials } from '@/lib/format'
import { useAppStore } from '@/stores/app'

const router = useRouter()
const app = useAppStore()
const { t } = useI18n()

function go(name: string) {
  void router.push({ name })
}

async function logout() {
  await app.logout()
  await router.push({ name: 'login' })
}
</script>

<template>
  <header class="flex items-center justify-between px-4 py-3">
    <LogoMark />
    <DropdownMenu>
      <DropdownMenuTrigger
        class="grid size-11 place-items-center rounded-full"
        :aria-label="t('nav.profile')"
      >
        <Avatar>
          <AvatarFallback>{{ initials(`${app.profile.firstName} ${app.profile.lastName}`.trim() || appName) }}</AvatarFallback>
        </Avatar>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" class="w-52">
        <DropdownMenuItem class="min-h-11" @click="go('profile')">
          <UserRound />
          {{ t('nav.profile') }}
        </DropdownMenuItem>
        <DropdownMenuItem class="min-h-11" @click="go('settings')">
          <Settings />
          {{ t('nav.settings') }}
        </DropdownMenuItem>
        <DropdownMenuItem class="min-h-11" @click="go('activities')">
          <Dumbbell />
          {{ t('nav.activities') }}
        </DropdownMenuItem>
        <DropdownMenuItem class="min-h-11" @click="go('weight')">
          <Scale />
          {{ t('nav.weight') }}
        </DropdownMenuItem>
        <DropdownMenuSeparator />
        <DropdownMenuItem class="min-h-11" @click="logout">
          <LogOut />
          {{ t('nav.logout') }}
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  </header>
</template>
