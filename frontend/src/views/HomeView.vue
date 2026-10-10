<script setup lang="ts">
import { appName } from '@/lib/appName'
</script>

<template>
  <main class="flex min-h-svh flex-col items-center justify-center gap-3 bg-background px-6 text-center text-foreground">
    <p class="text-sm text-muted-foreground">{{ appName }}</p>
    <h1 class="text-3xl font-semibold tracking-tight">Shell de development</h1>
    <p class="max-w-md text-muted-foreground">
      Frontend-ul și API-ul sunt inițializate. Funcționalitățile aplicației nu sunt implementate încă.
    </p>
  </main>
</template>
