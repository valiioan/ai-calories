import { computed, ref, watch } from 'vue'
import { defineStore } from 'pinia'
import { toast } from 'vue-sonner'
import { ApiError, apiJson, ensureCsrf } from '@/lib/api'
import { addDays, todayIn } from '@/lib/dates'
import {
  activityPayload,
  emptyDay,
  mealPayload,
  proposalPatch,
  toActivity,
  toBaseline,
  toChatMessage,
  toMeal,
  toProposal,
  toWeight,
  unwrap,
  type ApiActivity,
  type ApiMeal,
  type ApiMessage,
  type ApiProposal,
  type ApiSummary,
  type ApiWeight,
} from '@/lib/journal'
import { translate } from '@/i18n'
import { uid } from '@/lib/id'
import type {
  Activity,
  ActivityLevel,
  Appearance,
  ChatMessage,
  DayBaseline,
  DemoUser,
  Goal,
  Locale,
  Meal,
  MealProposal,
  Notifications,
  Period,
  Profile,
  Proposal,
  Units,
  ViewStatus,
  WeightPoint,
  WeightRange,
} from '@/types/domain'

const ONBOARDING_KEY = 'calorai-onboarding-pending'
const EARLIEST = '2000-01-01'

type AccountUser = {
  id: number
  first_name: string
  last_name: string
  email: string
}

type ProfilePayload = {
  birth_date: string | null
  height_cm: number | null
  activity_level: ActivityLevel | null
  goal: Goal
  calorie_target: number | null
  target_weight_kg: number | null
  timezone: string
}

type SettingsPayload = {
  locale: Locale
  appearance: Appearance
  units: Units
  notify_meals: boolean
  notify_weight: boolean
  notify_weekly: boolean
}

type ChatReply = {
  user_message: ApiMessage
  assistant_message: ApiMessage
  proposal: ApiProposal | null
  retry: boolean
}

function persistedId(id: string): string | null {
  return /^\d+$/.test(id) ? id : null
}

function emptyProfile(): Profile {
  return {
    firstName: '',
    lastName: '',
    email: '',
    dob: '',
    heightCm: null,
    weightKg: 0,
    targetWeightKg: null,
    activityLevel: 'light',
    goal: 'maintain',
    calorieTarget: null,
    units: 'metric',
    timezone: 'Europe/Bucharest',
  }
}

export const useAppStore = defineStore('app', () => {
  const user = ref<DemoUser | null>(null)
  const freshAccount = ref(false)
  const onboarded = ref(false)
  const locale = ref<Locale>('ro')
  const appearance = ref<Appearance>('light')
  const selectedDate = ref(todayIn())
  const viewStatus = ref<ViewStatus>('ready')
  const meals = ref<Record<string, Meal[]>>({})
  const activities = ref<Activity[]>([])
  const weights = ref<WeightPoint[]>([])
  const summaries = ref<Record<string, DayBaseline>>({})
  const messages = ref<ChatMessage[]>([])
  const conversationId = ref<number | null>(null)
  const chatThinking = ref(false)
  const profile = ref<Profile>(emptyProfile())
  const notifications = ref<Notifications>({ meals: true, weight: false, weekly: true })
  const aiDockOpen = ref(true)
  const navCollapsed = ref(false)
  const composerFocused = ref(false)
  const period = ref<Period>('7')
  const customFrom = ref(addDays(selectedDate.value, -6))
  const customTo = ref(selectedDate.value)
  const weightRange = ref<WeightRange>('30d')
  const earliest = EARLIEST
  let journalBooted = false
  let dateToken = 0
  let settingsToken = 0

  const today = computed(() => todayIn(profile.value.timezone || 'Europe/Bucharest'))

  function applyChrome() {
    const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches
    const dark = appearance.value === 'dark' || (appearance.value === 'system' && systemDark)
    document.documentElement.classList.toggle('dark', dark)
    document.documentElement.lang = locale.value === 'en' ? 'en' : 'ro'
  }

  applyChrome()
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', applyChrome)
  watch([appearance, locale], applyChrome)

  function t(key: Parameters<typeof translate>[1], params?: Record<string, string | number>) {
    return translate(locale.value, key, params)
  }

  function notifySaved() {
    toast.success(t('account.savedTitle'), { description: t('account.savedBody') })
  }

  function mealsOn(date: string): Meal[] {
    return meals.value[date] ?? []
  }

  function nutrition(date: string): DayBaseline {
    return summaries.value[date] ?? emptyDay()
  }

  const todayNutrition = computed(() => nutrition(selectedDate.value))
  const exerciseCalories = computed(() =>
    activities.value.filter((item) => item.date === selectedDate.value).reduce((sum, item) => sum + item.calories, 0),
  )
  const latestWeight = computed(() =>
    [...weights.value].sort((a, b) => (a.date < b.date ? 1 : -1))[0] ?? null,
  )
  const pendingMeals = computed(() =>
    messages.value
      .map((message) => message.proposal)
      .filter((proposal): proposal is MealProposal => proposal?.kind === 'meal' && proposal.status === 'pending'),
  )

  function visibleWindow() {
    const current = today.value
    const reportFrom = period.value === 'custom' ? customFrom.value : addDays(current, -(Number(period.value) - 1))
    const weightDays = weightRange.value === '7d' ? 7 : weightRange.value === '90d' ? 90 : weightRange.value === '1y' ? 365 : 30
    const from = [addDays(selectedDate.value, -6), reportFrom, addDays(current, -(weightDays - 1)), selectedDate.value]
      .reduce((left, right) => (left < right ? left : right))
    const to = [selectedDate.value, current, customTo.value].reduce((left, right) => (left > right ? left : right))
    return { from: from < earliest ? earliest : from, to: to > current ? current : to }
  }

  async function refreshJournal() {
    if (!user.value) return
    const token = ++dateToken
    viewStatus.value = 'loading'
    try {
      const window = visibleWindow()
      const [mealRows, activityRows, weightRows, summaryRows] = await Promise.all([
        apiJson<ApiMeal[]>(`/api/meals?date=${selectedDate.value}`),
        apiJson<ApiActivity[]>(`/api/activities?from=${window.from}&to=${window.to}`),
        apiJson<ApiWeight[]>(`/api/weights?from=${window.from}&to=${window.to}`),
        apiJson<{ data: ApiSummary[] }>(`/api/summary?from=${window.from}&to=${window.to}`),
      ])
      if (token !== dateToken) return
      const dayMeals = unwrap(mealRows).map(toMeal)
      meals.value = { [selectedDate.value]: dayMeals }
      activities.value = unwrap(activityRows).map(toActivity)
      weights.value = unwrap(weightRows).map(toWeight)
      const next: Record<string, DayBaseline> = {}
      for (const row of summaryRows.data) next[row.date] = toBaseline(row)
      summaries.value = next
      profile.value = { ...profile.value, weightKg: latestWeight.value?.kg ?? 0 }
      viewStatus.value = 'ready'
    } catch {
      if (token !== dateToken) return
      viewStatus.value = 'error'
    }
  }

  async function setDate(iso: string) {
    if (iso > today.value || iso < earliest) return
    selectedDate.value = iso
    await refreshJournal()
  }

  async function retryView() {
    await refreshJournal()
  }

  function applySettings(settings: SettingsPayload) {
    locale.value = settings.locale
    appearance.value = settings.appearance
    profile.value = { ...profile.value, units: settings.units }
    notifications.value = {
      meals: settings.notify_meals,
      weight: settings.notify_weight,
      weekly: settings.notify_weekly,
    }
  }

  function applyProfile(account: AccountUser, payload: ProfilePayload) {
    user.value = {
      id: account.id,
      firstName: account.first_name,
      lastName: account.last_name,
      email: account.email,
    }
    profile.value = {
      ...profile.value,
      firstName: account.first_name,
      lastName: account.last_name,
      email: account.email,
      dob: payload.birth_date ?? '',
      heightCm: payload.height_cm,
      activityLevel: payload.activity_level ?? profile.value.activityLevel,
      goal: payload.goal,
      calorieTarget: payload.calorie_target,
      targetWeightKg: payload.target_weight_kg,
      timezone: payload.timezone,
    }
  }

  async function loadConversation() {
    const list = await apiJson<{ data: { id: number }[] }>('/api/conversations')
    let id = list.data[0]?.id
    if (!id) {
      const created = await apiJson<{ id: number }>('/api/conversations', { method: 'POST', body: JSON.stringify({}) })
      id = created.id
      messages.value = []
      conversationId.value = id
      return
    }
    const conversation = await apiJson<{ id: number; messages: ApiMessage[] }>(`/api/conversations/${id}`)
    conversationId.value = conversation.id
    messages.value = conversation.messages.map(toChatMessage)
  }

  async function loadAccount(account: AccountUser) {
    const [payload, settings] = await Promise.all([
      apiJson<ProfilePayload>('/api/profile'),
      apiJson<SettingsPayload>('/api/settings'),
    ])
    applyProfile(account, payload)
    applySettings(settings)
    if (!journalBooted) {
      selectedDate.value = today.value
      customTo.value = today.value
      customFrom.value = addDays(today.value, -6)
      journalBooted = true
    }
    await Promise.all([refreshJournal(), loadConversation()])
  }

  let sessionReady: Promise<void> | null = null

  function boot() {
    sessionReady ??= loadSession()
    return sessionReady
  }

  async function loadSession() {
    try {
      const account = await apiJson<AccountUser>('/api/user')
      await loadAccount(account)
      onboarded.value = sessionStorage.getItem(ONBOARDING_KEY) !== '1'
      freshAccount.value = false
    } catch (error) {
      user.value = null
      onboarded.value = false
      if (!(error instanceof ApiError) || error.status !== 401) {
        sessionReady = null
      }
    }
  }

  async function login(email: string, password: string) {
    await ensureCsrf()
    const account = await apiJson<AccountUser>('/login', {
      method: 'POST',
      body: JSON.stringify({ email, password, remember: true }),
    })
    journalBooted = false
    await loadAccount(account)
    sessionStorage.removeItem(ONBOARDING_KEY)
    onboarded.value = true
    freshAccount.value = false
  }

  async function register(firstName: string, lastName: string, email: string, password: string, confirmation: string) {
    await ensureCsrf()
    const account = await apiJson<AccountUser>('/register', {
      method: 'POST',
      body: JSON.stringify({
        first_name: firstName,
        last_name: lastName,
        email,
        password,
        password_confirmation: confirmation,
      }),
    })
    journalBooted = false
    await loadAccount(account)
    sessionStorage.setItem(ONBOARDING_KEY, '1')
    onboarded.value = false
    freshAccount.value = true
  }

  async function resetPassword(email: string, token: string, password: string, confirmation: string) {
    await ensureCsrf()
    const account = await apiJson<AccountUser>('/reset-password', {
      method: 'POST',
      body: JSON.stringify({ email, token, password, password_confirmation: confirmation }),
    })
    journalBooted = false
    await loadAccount(account)
    sessionStorage.removeItem(ONBOARDING_KEY)
    onboarded.value = true
    freshAccount.value = false
  }

  function profileBody(next: Profile) {
    return {
      birth_date: next.dob || null,
      height_cm: next.heightCm,
      activity_level: next.activityLevel,
      goal: next.goal,
      calorie_target: next.calorieTarget,
      target_weight_kg: next.targetWeightKg,
      timezone: next.timezone,
    }
  }

  async function persistProfile(next: Profile) {
    await ensureCsrf()
    const account = await apiJson<AccountUser>('/api/user', {
      method: 'PUT',
      body: JSON.stringify({ first_name: next.firstName, last_name: next.lastName, email: next.email }),
    })
    const saved = await apiJson<ProfilePayload>('/api/profile', {
      method: 'PUT',
      body: JSON.stringify(profileBody(next)),
    })
    applyProfile(account, saved)
    profile.value = { ...profile.value, units: next.units, weightKg: next.weightKg }
    if (next.weightKg >= 30) {
      await apiJson('/api/weights', {
        method: 'POST',
        body: JSON.stringify({ recorded_on: today.value, weight_kg: next.weightKg, note: null }),
      })
    }
    await refreshJournal()
  }

  async function finishOnboarding(next: Profile) {
    await persistProfile(next)
    await ensureCsrf()
    const settings = await apiJson<SettingsPayload>('/api/settings', {
      method: 'PUT',
      body: JSON.stringify({ units: next.units }),
    })
    applySettings(settings)
    sessionStorage.removeItem(ONBOARDING_KEY)
    onboarded.value = true
    notifySaved()
  }

  function clearJournal() {
    meals.value = {}
    activities.value = []
    weights.value = []
    summaries.value = {}
    messages.value = []
    conversationId.value = null
    journalBooted = false
  }

  async function logout() {
    try {
      await ensureCsrf()
      await apiJson('/logout', { method: 'POST' })
    } catch {
      // The cookie is cleared locally even if the request fails.
    }
    user.value = null
    onboarded.value = false
    freshAccount.value = false
    profile.value = emptyProfile()
    clearJournal()
  }

  async function saveProfile(next: Profile) {
    await persistProfile(next)
    notifySaved()
  }

  async function persistSettings(revert: () => void) {
    if (!user.value) return
    const token = ++settingsToken
    try {
      await ensureCsrf()
      const saved = await apiJson<SettingsPayload>('/api/settings', {
        method: 'PUT',
        body: JSON.stringify({
          locale: locale.value,
          appearance: appearance.value,
          units: profile.value.units,
          notify_meals: notifications.value.meals,
          notify_weight: notifications.value.weight,
          notify_weekly: notifications.value.weekly,
        }),
      })
      if (token !== settingsToken) return
      applySettings(saved)
    } catch {
      if (token !== settingsToken) return
      revert()
      toast.error(t('account.saveFailed'))
    }
  }

  function setAppearance(value: Appearance) {
    const previous = appearance.value
    appearance.value = value
    void persistSettings(() => {
      appearance.value = previous
    })
  }

  function setLocale(value: Locale) {
    const previous = locale.value
    locale.value = value
    void persistSettings(() => {
      locale.value = previous
    })
  }

  function setUnits(value: Units) {
    const previous = profile.value.units
    profile.value = { ...profile.value, units: value }
    void persistSettings(() => {
      profile.value = { ...profile.value, units: previous }
    })
  }

  function setNotification(key: keyof Notifications, value: boolean) {
    const previous = notifications.value[key]
    notifications.value = { ...notifications.value, [key]: value }
    void persistSettings(() => {
      notifications.value = { ...notifications.value, [key]: previous }
    })
  }

  async function setTimezone(value: string) {
    const previous = profile.value.timezone
    profile.value = { ...profile.value, timezone: value }
    if (!user.value) return
    try {
      await ensureCsrf()
      const saved = await apiJson<ProfilePayload>('/api/profile', {
        method: 'PUT',
        body: JSON.stringify({ timezone: value }),
      })
      profile.value = { ...profile.value, timezone: saved.timezone }
      if (selectedDate.value > today.value) selectedDate.value = today.value
      await refreshJournal()
    } catch {
      profile.value = { ...profile.value, timezone: previous }
      toast.error(t('account.saveFailed'))
    }
  }

  async function upsertMeal(meal: Meal, previousDate?: string) {
    await ensureCsrf()
    const id = persistedId(meal.id)
    const path = id ? `/api/meals/${id}` : '/api/meals'
    await apiJson(path, { method: id ? 'PUT' : 'POST', body: JSON.stringify(mealPayload(meal)) })
    if (previousDate && previousDate !== meal.date) selectedDate.value = meal.date
    else if (meal.date !== selectedDate.value) selectedDate.value = meal.date
    await refreshJournal()
    toast.success(t('journal.savedTitle'), { description: t('journal.savedBody') })
  }

  async function deleteMeal(id: string) {
    await ensureCsrf()
    await apiJson(`/api/meals/${id}`, { method: 'DELETE' })
    await refreshJournal()
    toast.success(t('journal.savedTitle'), { description: t('journal.deletedBody') })
  }

  async function upsertActivity(activity: Activity) {
    await ensureCsrf()
    const id = persistedId(activity.id)
    const path = id ? `/api/activities/${id}` : '/api/activities'
    await apiJson(path, { method: id ? 'PUT' : 'POST', body: JSON.stringify(activityPayload(activity)) })
    if (activity.date !== selectedDate.value) selectedDate.value = activity.date
    await refreshJournal()
    toast.success(t('journal.savedTitle'), { description: t('journal.savedBody') })
  }

  async function deleteActivity(id: string) {
    await ensureCsrf()
    await apiJson(`/api/activities/${id}`, { method: 'DELETE' })
    await refreshJournal()
    toast.success(t('journal.savedTitle'), { description: t('journal.deletedBody') })
  }

  async function upsertWeight(point: WeightPoint) {
    await ensureCsrf()
    const id = persistedId(point.id)
    const path = id ? `/api/weights/${id}` : '/api/weights'
    await apiJson(path, {
      method: id ? 'PUT' : 'POST',
      body: JSON.stringify({ recorded_on: point.date, weight_kg: point.kg, note: point.notes || null }),
    })
    await refreshJournal()
    toast.success(t('journal.savedTitle'), { description: t('journal.savedBody') })
  }

  async function deleteWeight(id: string) {
    await ensureCsrf()
    await apiJson(`/api/weights/${id}`, { method: 'DELETE' })
    await refreshJournal()
    toast.success(t('journal.savedTitle'), { description: t('journal.deletedBody') })
  }

  async function sendChat(text: string) {
    const trimmed = text.trim()
    if (!trimmed || chatThinking.value) return
    chatThinking.value = true
    try {
      await ensureCsrf()
      if (!conversationId.value) await loadConversation()
      const reply = await apiJson<ChatReply>(`/api/conversations/${conversationId.value}/messages`, {
        method: 'POST',
        body: JSON.stringify({ content: trimmed }),
      })
      const assistant = toChatMessage(reply.assistant_message)
      if (reply.proposal) assistant.proposal = toProposal(reply.proposal)
      if (reply.retry) assistant.retryText = trimmed
      messages.value.push(toChatMessage(reply.user_message), assistant)
    } catch {
      messages.value.push(
        { id: uid('msg'), role: 'user', text: trimmed, at: new Date().toISOString() },
        { id: uid('msg'), role: 'assistant', text: t('chat.unavailable'), at: new Date().toISOString(), retryText: trimmed },
      )
    } finally {
      chatThinking.value = false
    }
  }

  async function retryChat(text: string) {
    await sendChat(text)
  }

  function replaceProposal(id: string, proposal: Proposal) {
    const message = messages.value.find((item) => item.proposal?.id === id)
    if (message) message.proposal = proposal
  }

  async function confirmProposal(id: string) {
    if (chatThinking.value) return
    chatThinking.value = true
    try {
      await ensureCsrf()
      const saved = await apiJson<{ proposal: ApiProposal; revision_id: number }>(`/api/proposals/${id}/confirm`, { method: 'POST' })
      const message = messages.value.find((item) => item.proposal?.id === id)
      if (message) {
        message.proposal = toProposal(saved.proposal)
        message.revisionId = String(saved.revision_id)
        message.text = saved.proposal.kind === 'delete-meal'
          ? t('chat.deleted')
          : saved.proposal.kind === 'weight'
            ? t('chat.weightSaved')
            : saved.proposal.kind === 'activity'
              ? t('chat.activitySaved')
              : t('chat.saved')
      }
      await refreshJournal()
      toast.success(t('journal.savedTitle'), { description: t('journal.savedBody') })
    } catch {
      toast.error(t('chat.actionFailed'))
    } finally {
      chatThinking.value = false
    }
  }

  async function cancelProposal(id: string) {
    try {
      await ensureCsrf()
      const saved = await apiJson<ApiProposal>(`/api/proposals/${id}/cancel`, { method: 'POST' })
      const message = messages.value.find((item) => item.proposal?.id === id)
      if (message) {
        message.proposal = toProposal(saved)
        message.text = t('chat.cancelled')
      }
    } catch {
      toast.error(t('chat.actionFailed'))
    }
  }

  async function patchProposal(id: string, next: Proposal) {
    await ensureCsrf()
    const saved = await apiJson<ApiProposal>(`/api/proposals/${id}`, {
      method: 'PATCH',
      body: JSON.stringify(proposalPatch(next)),
    })
    replaceProposal(id, toProposal(saved))
    const message = messages.value.find((item) => item.proposal?.id === id)
    if (message) message.text = t('chat.stillOpen')
  }

  async function correctMealProposal(id: string, meal: Meal) {
    const current = messages.value.find((item) => item.proposal?.id === id)?.proposal
    if (!current || current.kind !== 'meal') return
    await patchProposal(id, { ...current, corrected: true, meal })
  }

  async function correctWeightProposal(id: string, kg: number, notes: string) {
    const current = messages.value.find((item) => item.proposal?.id === id)?.proposal
    if (!current || current.kind !== 'weight') return
    await patchProposal(id, { ...current, kg, notes })
  }

  async function correctActivityProposal(id: string, activity: Omit<Activity, 'id'>) {
    const current = messages.value.find((item) => item.proposal?.id === id)?.proposal
    if (!current || current.kind !== 'activity') return
    await patchProposal(id, { ...current, activity })
  }

  async function undoRevision(revisionId: string) {
    try {
      await ensureCsrf()
      await apiJson(`/api/journal-revisions/${revisionId}/undo`, { method: 'POST' })
      const message = messages.value.find((item) => item.revisionId === revisionId)
      if (message) {
        message.revisionId = undefined
        message.text = t('chat.undone')
      }
      await refreshJournal()
      toast.success(t('chat.undone'))
    } catch {
      toast.error(t('chat.actionFailed'))
    }
  }

  watch([period, customFrom, customTo, weightRange], () => {
    if (user.value) void refreshJournal()
  })

  return {
    user,
    freshAccount,
    onboarded,
    locale,
    appearance,
    selectedDate,
    viewStatus,
    meals,
    activities,
    weights,
    messages,
    chatThinking,
    profile,
    notifications,
    aiDockOpen,
    navCollapsed,
    composerFocused,
    period,
    customFrom,
    customTo,
    weightRange,
    earliest,
    today,
    todayNutrition,
    exerciseCalories,
    latestWeight,
    mealsOn,
    nutrition,
    setDate,
    retryView,
    login,
    register,
    resetPassword,
    finishOnboarding,
    logout,
    saveProfile,
    setAppearance,
    setLocale,
    setUnits,
    setNotification,
    setTimezone,
    boot,
    upsertMeal,
    deleteMeal,
    upsertActivity,
    deleteActivity,
    upsertWeight,
    deleteWeight,
    sendChat,
    retryChat,
    confirmProposal,
    cancelProposal,
    correctMealProposal,
    correctWeightProposal,
    correctActivityProposal,
    undoRevision,
    pendingMeals,
  }
})
