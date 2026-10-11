<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue'
import { ArrowLeft, ArrowUp, Maximize2, Sparkles, X } from '@lucide/vue'
import { useRouter } from 'vue-router'
import { Button } from '@/components/ui/button'
import { Empty, EmptyDescription, EmptyHeader, EmptyTitle } from '@/components/ui/empty'
import { Input } from '@/components/ui/input'
import { Spinner } from '@/components/ui/spinner'
import { Textarea } from '@/components/ui/textarea'
import ProposalCard from '@/components/chat/ProposalCard.vue'
import FormField from '@/components/forms/FormField.vue'
import MealForm from '@/components/meals/MealForm.vue'
import ResponsivePanel from '@/components/layout/ResponsivePanel.vue'
import { useI18n } from '@/composables/useI18n'
import { useMediaQuery } from '@/composables/useMediaQuery'
import { formatTime } from '@/lib/format'
import { useAppStore } from '@/stores/app'
import type { Activity, Meal } from '@/types/domain'

const props = defineProps<{ layout: 'page' | 'dock' }>()
const app = useAppStore()
const router = useRouter()
const { t } = useI18n()
const reduceMotion = useMediaQuery('(prefers-reduced-motion: reduce)')
const draft = ref('')
const scroller = ref<HTMLElement | null>(null)
const editingMeal = ref<Meal | null>(null)
const editingId = ref('')
const weightOpen = ref(false)
const weightKg = ref('')
const weightNotes = ref('')
const activityOpen = ref(false)
const activityDraft = ref<Omit<Activity, 'id'> | null>(null)

const suggestions = computed(() => app.locale === 'en'
  ? ['Add breakfast', 'Log my weight', 'I exercised', 'Show yesterday calories']
  : ['Adaugă micul dejun', 'Înregistrează greutatea', 'Am făcut sport', 'Arată-mi caloriile de ieri'])

const mealOpen = computed({
  get: () => editingMeal.value != null,
  set: (value: boolean) => {
    if (!value) editingMeal.value = null
  },
})

async function scrollDown() {
  await nextTick()
  scroller.value?.scrollTo({ top: scroller.value.scrollHeight, behavior: reduceMotion.value ? 'auto' : 'smooth' })
}

watch(() => [app.messages.length, app.chatThinking], scrollDown)

function send(text = draft.value) {
  const value = text.trim()
  if (!value) return
  draft.value = ''
  void app.sendChat(value)
}

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    send()
  }
}

function correct(id: string) {
  const proposal = app.messages.find((message) => message.proposal?.id === id)?.proposal
  if (!proposal || proposal.status !== 'pending') return
  editingId.value = id
  if (proposal.kind === 'meal') editingMeal.value = structuredClone(proposal.meal)
  if (proposal.kind === 'weight') {
    weightKg.value = String(proposal.kg)
    weightNotes.value = proposal.notes
    weightOpen.value = true
  }
  if (proposal.kind === 'activity') {
    activityDraft.value = { ...proposal.activity }
    activityOpen.value = true
  }
}

function saveMeal(meal: Meal) {
  app.correctMealProposal(editingId.value, meal)
  editingMeal.value = null
}

function saveWeight() {
  const kg = Number(weightKg.value.replace(',', '.'))
  if (!Number.isFinite(kg) || kg < 30 || kg > 300) return
  app.correctWeightProposal(editingId.value, Math.round(kg * 10) / 10, weightNotes.value)
  weightOpen.value = false
}

function saveActivity() {
  if (!activityDraft.value) return
  if (!activityDraft.value.name.trim() || activityDraft.value.minutes < 1) return
  app.correctActivityProposal(editingId.value, { ...activityDraft.value, name: activityDraft.value.name.trim() })
  activityOpen.value = false
}
</script>

<template>
  <section class="flex h-full min-h-0 flex-col bg-background" :class="props.layout === 'page' ? 'md:h-[calc(100svh-7rem)]' : 'h-full'">
    <header class="flex items-center gap-2 border-b border-border px-3 py-3">
      <Button v-if="props.layout === 'page'" type="button" variant="ghost" size="icon" class="size-11 md:hidden" :aria-label="t('chat.back')" @click="router.push({ name: 'dashboard' })">
        <ArrowLeft />
      </Button>
      <span class="grid size-10 shrink-0 place-items-center rounded-full bg-secondary text-primary" aria-hidden="true">
        <Sparkles />
      </span>
      <div class="min-w-0 flex-1">
        <h1 class="text-sm font-semibold leading-tight">{{ t('chat.title') }}</h1>
        <p class="text-xs text-muted-foreground">{{ t('chat.subtitle') }}</p>
      </div>
      <Button v-if="props.layout === 'dock'" type="button" variant="ghost" size="icon" class="size-11" :aria-label="t('chat.expand')" @click="router.push({ name: 'chat' })">
        <Maximize2 />
      </Button>
      <Button v-if="props.layout === 'dock'" type="button" variant="ghost" size="icon" class="size-11" :aria-label="t('chat.collapse')" @click="app.aiDockOpen = false">
        <X />
      </Button>
      <Button v-if="props.layout === 'page'" type="button" variant="outline" class="hidden h-11 xl:inline-flex" @click="app.aiDockOpen = true; router.push({ name: 'dashboard' })">
        {{ t('chat.dock') }}
      </Button>
    </header>

    <div ref="scroller" class="flex min-h-0 flex-1 flex-col gap-3 overflow-y-auto px-3 py-4" aria-live="polite">
      <Empty v-if="app.messages.length === 0 && !app.chatThinking">
        <EmptyHeader>
          <EmptyTitle>{{ t('chat.emptyTitle') }}</EmptyTitle>
          <EmptyDescription>{{ t('chat.emptyBody') }}</EmptyDescription>
        </EmptyHeader>
      </Empty>
      <article v-for="message in app.messages" :key="message.id" class="grid gap-2" :class="message.role === 'user' ? 'justify-items-end' : 'justify-items-start'">
        <div
          class="max-w-[85%] rounded-2xl px-3 py-2 text-sm"
          :class="message.role === 'user' ? 'bg-primary text-primary-foreground' : 'bg-muted text-foreground'"
        >
          <p class="whitespace-pre-wrap break-words">{{ message.text }}</p>
          <time class="mt-1 block text-xs opacity-80" :datetime="message.at">{{ formatTime(message.at, app.locale) }}</time>
        </div>
        <Button v-if="message.revisionId" type="button" variant="outline" class="h-11" @click="app.undoRevision(message.revisionId)">
          {{ t('chat.undo') }}
        </Button>
        <ProposalCard
          v-if="message.proposal && message.proposal.status !== 'cancelled'"
          class="w-full"
          :proposal="message.proposal"
          @confirm="app.confirmProposal(message.proposal.id)"
          @cancel="app.cancelProposal(message.proposal.id)"
          @correct="correct(message.proposal.id)"
        />
        <Button v-if="message.retryText" type="button" variant="outline" class="h-11" :disabled="app.chatThinking" @click="app.retryChat(message.retryText)">
          {{ t('chat.retry') }}
        </Button>
      </article>
      <div v-if="app.chatThinking" class="flex items-center gap-2 rounded-2xl bg-muted px-3 py-2 text-sm">
        <Spinner />
        <span>{{ t('chat.thinking') }}</span>
      </div>
    </div>

    <form class="grid gap-2 border-t border-border p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]" @submit.prevent="send()">
      <div class="flex gap-2 overflow-x-auto">
        <Button v-for="chip in suggestions" :key="chip" type="button" variant="outline" class="h-11 shrink-0" @click="send(chip)">
          {{ chip }}
        </Button>
      </div>
      <div class="flex items-end gap-2">
        <Textarea
          v-model="draft"
          rows="2"
          class="min-h-11 flex-1"
          :placeholder="t('chat.placeholder')"
          :aria-label="t('chat.placeholder')"
          :disabled="app.chatThinking"
          @focus="app.composerFocused = true"
          @blur="app.composerFocused = false"
          @keydown="onKeydown"
        />
        <Button type="submit" size="icon" class="size-11 hover:bg-primary-hover" :disabled="app.chatThinking || !draft.trim()" :aria-label="t('chat.send')">
          <ArrowUp />
        </Button>
      </div>
      <p class="text-xs text-muted-foreground">{{ t('chat.disclaimer') }}</p>
    </form>

    <ResponsivePanel v-if="editingMeal" v-model:open="mealOpen" :title="t('chat.correct')" :description="t('chat.stillOpen')">
      <MealForm :meal="editingMeal" @save="saveMeal" @cancel="editingMeal = null" />
    </ResponsivePanel>
    <ResponsivePanel v-model:open="weightOpen" :title="t('chat.correct')" :description="t('chat.stillOpen')">
      <form class="grid gap-3" @submit.prevent="saveWeight">
        <FormField id="proposal-weight" :label="t('weight.value')">
          <Input id="proposal-weight" v-model="weightKg" inputmode="decimal" class="h-11" />
        </FormField>
        <FormField id="proposal-weight-notes" :label="t('weight.notes')" :optional="t('common.optional')">
          <Input id="proposal-weight-notes" v-model="weightNotes" class="h-11" />
        </FormField>
        <Button type="submit" class="h-11">{{ t('common.save') }}</Button>
      </form>
    </ResponsivePanel>
    <ResponsivePanel v-model:open="activityOpen" :title="t('chat.correct')" :description="t('chat.stillOpen')">
      <form v-if="activityDraft" class="grid gap-3" @submit.prevent="saveActivity">
        <FormField id="proposal-activity" :label="t('activity.name')">
          <Input id="proposal-activity" v-model="activityDraft.name" class="h-11" />
        </FormField>
        <FormField id="proposal-minutes" :label="t('activity.duration')">
          <Input id="proposal-minutes" v-model.number="activityDraft.minutes" type="number" min="1" class="h-11" />
        </FormField>
        <Button type="submit" class="h-11">{{ t('common.save') }}</Button>
      </form>
    </ResponsivePanel>
  </section>
</template>
