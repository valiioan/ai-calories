<script setup lang="ts">
import { computed, ref } from 'vue'
import { Eye, EyeOff } from '@lucide/vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { useI18n } from '@/composables/useI18n'

const model = defineModel<string>({ required: true })
const props = defineProps<{
  id: string
  invalid?: boolean
  autocomplete?: string
}>()

const { t } = useI18n()
const visible = ref(false)
const describedBy = computed(() => (props.invalid ? `${props.id}-error` : undefined))
</script>

<template>
  <div class="relative">
    <Input
      :id="id"
      v-model="model"
      :type="visible ? 'text' : 'password'"
      class="h-11 pr-12"
      :autocomplete="autocomplete"
      :aria-invalid="invalid || undefined"
      :aria-describedby="describedBy"
    />
    <Button
      type="button"
      variant="ghost"
      size="icon"
      class="absolute top-0 right-0 size-11"
      :aria-pressed="visible"
      :aria-label="visible ? t('auth.passwordHide') : t('auth.passwordShow')"
      @click="visible = !visible"
    >
      <EyeOff v-if="visible" />
      <Eye v-else />
    </Button>
  </div>
</template>
