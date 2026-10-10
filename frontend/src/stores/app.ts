import { computed, ref, watch } from 'vue'
import { defineStore } from 'pinia'
import { toast } from 'vue-sonner'
import { buildBaselines, initialActivities, initialMeals, initialWeights, mealCalories } from '@/fixtures/demo'
import { ApiError, apiJson, ensureCsrf } from '@/lib/api'
import { interpret, YESTERDAY } from '@/fixtures/interpret'
import { translate } from '@/i18n'
import { addDays, TODAY } from '@/lib/dates'
import { formatKcal, formatLongDate, localizedName } from '@/lib/format'
import { delay, uid } from '@/lib/id'
import type {
  Activity,
  ActivityLevel,
  ActivityProposal,
  Appearance,
  ChatMessage,
  DayBaseline,
  DemoUser,
  ExampleKind,
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

const STORAGE_KEY = 'calorai-demo-v1'
const ONBOARDING_KEY = 'calorai-onboarding-pending'

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

type Persisted = {
  version: 1
  user: DemoUser | null
  onboarded: boolean
  locale: Locale
  appearance: Appearance
  selectedDate: string
  meals: Record<string, Meal[]>
  activities: Activity[]
  weights: WeightPoint[]
  messages: ChatMessage[]
  profile: Profile
  notifications: Notifications
  aiDockOpen: boolean
  navCollapsed: boolean
  period: Period
  customFrom: string
  customTo: string
  weightRange: WeightRange
}

function emptyProfile(): Profile {
  return {
    firstName: '',
    lastName: '',
    email: '',
    dob: '',
    heightCm: null,
    weightKg: 84.6,
    targetWeightKg: 80,
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
  const selectedDate = ref(TODAY)
  const viewStatus = ref<ViewStatus>('ready')
  const meals = ref<Record<string, Meal[]>>(initialMeals())
  const activities = ref<Activity[]>(initialActivities())
  const weights = ref<WeightPoint[]>(initialWeights())
  const messages = ref<ChatMessage[]>([])
  const chatThinking = ref(false)
  const profile = ref<Profile>(emptyProfile())
  const notifications = ref<Notifications>({ meals: true, weight: false, weekly: true })
  const aiDockOpen = ref(true)
  const navCollapsed = ref(false)
  const composerFocused = ref(false)
  const period = ref<Period>('7')
  const customFrom = ref(addDays(TODAY, -6))
  const customTo = ref(TODAY)
  const weightRange = ref<WeightRange>('30d')
  const baselines: Record<string, DayBaseline> = buildBaselines()
  let unavailablePending = false
  let dateToken = 0

  function applyChrome() {
    const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches
    const dark = appearance.value === 'dark' || (appearance.value === 'system' && systemDark)
    document.documentElement.classList.toggle('dark', dark)
    document.documentElement.lang = locale.value === 'en' ? 'en' : 'ro'
  }

  function snapshot(): Persisted {
    return {
      version: 1,
      user: null,
      onboarded: false,
      locale: locale.value,
      appearance: appearance.value,
      selectedDate: selectedDate.value,
      meals: meals.value,
      activities: activities.value,
      weights: weights.value,
      messages: messages.value,
      profile: profile.value,
      notifications: notifications.value,
      aiDockOpen: aiDockOpen.value,
      navCollapsed: navCollapsed.value,
      period: period.value,
      customFrom: customFrom.value,
      customTo: customTo.value,
      weightRange: weightRange.value,
    }
  }

  function hydrate() {
    const raw = sessionStorage.getItem(STORAGE_KEY)
    if (!raw) return
    try {
      const data = JSON.parse(raw) as Persisted
      if (data.version !== 1) return
      locale.value = data.locale
      appearance.value = data.appearance
      selectedDate.value = data.selectedDate
      meals.value = data.meals
      activities.value = data.activities
      weights.value = data.weights
      messages.value = data.messages
      profile.value = { ...emptyProfile(), ...data.profile, firstName: '', lastName: '', email: '' }
      notifications.value = data.notifications
      aiDockOpen.value = data.aiDockOpen
      navCollapsed.value = data.navCollapsed
      period.value = data.period
      customFrom.value = data.customFrom
      customTo.value = data.customTo
      weightRange.value = data.weightRange
    } catch {
      sessionStorage.removeItem(STORAGE_KEY)
    }
  }

  hydrate()
  applyChrome()
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', applyChrome)

  watch([appearance, locale], applyChrome)
  watch(
    [user, onboarded, locale, appearance, selectedDate, meals, activities, weights, messages, profile, notifications, aiDockOpen, navCollapsed, period, customFrom, customTo, weightRange],
    () => sessionStorage.setItem(STORAGE_KEY, JSON.stringify(snapshot())),
    { deep: true },
  )

  function t(key: Parameters<typeof translate>[1], params?: Record<string, string | number>) {
    return translate(locale.value, key, params)
  }

  function notifyDemo() {
    toast.success(t('demo.savedTitle'), { description: t('demo.savedBody') })
  }

  const earliest = addDays(TODAY, -364)

  function mealsOn(date: string): Meal[] {
    return meals.value[date] ?? []
  }

  function nutrition(date: string) {
    const dayMeals = meals.value[date]
    const base = baselines[date]
    const expenditure = base?.expenditure ?? 2400
    if (dayMeals) {
      const foods = dayMeals.flatMap((meal) => meal.items)
      return {
        intake: foods.reduce((sum, food) => sum + food.calories, 0),
        expenditure,
        protein: foods.reduce((sum, food) => sum + (food.protein ?? 0), 0),
        carbs: foods.reduce((sum, food) => sum + (food.carbs ?? 0), 0),
        fat: foods.reduce((sum, food) => sum + (food.fat ?? 0), 0),
        logged: dayMeals.length > 0,
      }
    }
    if (!base || date > TODAY || date < earliest) {
      return { intake: 0, expenditure: 0, protein: 0, carbs: 0, fat: 0, logged: false }
    }
    return base
  }

  const todayNutrition = computed(() => nutrition(selectedDate.value))
  const exerciseCalories = computed(() =>
    activities.value.filter((item) => item.date === selectedDate.value).reduce((sum, item) => sum + item.calories, 0),
  )
  const latestWeight = computed(() =>
    [...weights.value].sort((a, b) => (a.date < b.date ? 1 : -1))[0] ?? null,
  )

  async function setDate(iso: string) {
    if (iso > TODAY || iso < earliest) return
    selectedDate.value = iso
    const token = ++dateToken
    viewStatus.value = 'loading'
    await delay(350)
    if (token !== dateToken) return
    viewStatus.value = 'ready'
  }

  function showError() {
    viewStatus.value = 'error'
  }

  async function retryView() {
    const token = ++dateToken
    viewStatus.value = 'loading'
    await delay(500)
    if (token !== dateToken) return
    viewStatus.value = 'ready'
  }

  function loadDemoJournal() {
    meals.value = initialMeals()
    activities.value = initialActivities()
    weights.value = initialWeights()
  }

  function notifySaved() {
    toast.success(t('account.savedTitle'), { description: t('account.savedBody') })
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
    const activity = payload.activity_level ?? profile.value.activityLevel
    profile.value = {
      ...profile.value,
      firstName: account.first_name,
      lastName: account.last_name,
      email: account.email,
      dob: payload.birth_date ?? '',
      heightCm: payload.height_cm,
      activityLevel: activity,
      goal: payload.goal,
      calorieTarget: payload.calorie_target,
      timezone: payload.timezone,
    }
  }

  async function loadAccount(account: AccountUser) {
    const [payload, settings] = await Promise.all([
      apiJson<ProfilePayload>('/api/profile'),
      apiJson<SettingsPayload>('/api/settings'),
    ])
    applyProfile(account, payload)
    applySettings(settings)
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
    await loadAccount(account)
    sessionStorage.setItem(ONBOARDING_KEY, '1')
    onboarded.value = false
    freshAccount.value = true
  }

  async function resetPassword(email: string, token: string, password: string, confirmation: string) {
    await ensureCsrf()
    const account = await apiJson<AccountUser>('/reset-password', {
      method: 'POST',
      body: JSON.stringify({
        email,
        token,
        password,
        password_confirmation: confirmation,
      }),
    })
    await loadAccount(account)
    sessionStorage.removeItem(ONBOARDING_KEY)
    onboarded.value = true
    freshAccount.value = false
  }

  function syncTodayWeight(kg: number) {
    const todayPoint = weights.value.find((point) => point.date === TODAY)
    if (todayPoint) todayPoint.kg = kg
    else weights.value.push({ id: uid('wt'), date: TODAY, kg, notes: '' })
  }

  function profileBody(next: Profile) {
    return {
      birth_date: next.dob || null,
      height_cm: next.heightCm,
      activity_level: next.activityLevel,
      goal: next.goal,
      calorie_target: next.calorieTarget,
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
    profile.value = {
      ...profile.value,
      weightKg: next.weightKg,
      targetWeightKg: next.targetWeightKg,
      units: next.units,
    }
    syncTodayWeight(next.weightKg)
  }

  async function finishOnboarding(next: Profile) {
    await persistProfile(next)
    await ensureCsrf()
    const settings = await apiJson<SettingsPayload>('/api/settings', {
      method: 'PUT',
      body: JSON.stringify({ units: next.units }),
    })
    applySettings(settings)
    if (Object.keys(meals.value).length === 0) loadDemoJournal()
    sessionStorage.removeItem(ONBOARDING_KEY)
    onboarded.value = true
    notifySaved()
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
    messages.value = []
    profile.value = { ...profile.value, firstName: '', lastName: '', email: '' }
    freshAccount.value = false
  }

  async function saveProfile(next: Profile) {
    await persistProfile(next)
    notifySaved()
  }

  let settingsToken = 0

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
    } catch {
      profile.value = { ...profile.value, timezone: previous }
      toast.error(t('account.saveFailed'))
    }
  }

  function upsertMeal(meal: Meal, previousDate?: string) {
    const next = { ...meal, id: meal.id || uid('meal'), items: meal.items.map((item) => ({ ...item, id: item.id || uid('food'), nameEn: item.nameEn && item.nameEn !== item.name ? item.nameEn : undefined })) }
    if (previousDate && previousDate !== next.date) {
      meals.value[previousDate] = (meals.value[previousDate] ?? []).filter((item) => item.id !== next.id)
    }
    const list = [...(meals.value[next.date] ?? [])]
    const index = list.findIndex((item) => item.id === next.id)
    if (index >= 0) list.splice(index, 1, next)
    else list.push(next)
    meals.value[next.date] = list
    notifyDemo()
  }

  function deleteMeal(id: string, date: string) {
    meals.value[date] = (meals.value[date] ?? []).filter((item) => item.id !== id)
    notifyDemo()
  }

  function upsertActivity(activity: Activity) {
    const next = { ...activity, id: activity.id || uid('act') }
    const index = activities.value.findIndex((item) => item.id === next.id)
    if (index >= 0) activities.value.splice(index, 1, next)
    else activities.value.push(next)
    notifyDemo()
  }

  function deleteActivity(id: string) {
    activities.value = activities.value.filter((item) => item.id !== id)
    notifyDemo()
  }

  function upsertWeight(point: WeightPoint) {
    const next = { ...point, id: point.id || uid('wt') }
    const index = weights.value.findIndex((item) => item.id === next.id)
    if (index >= 0) weights.value.splice(index, 1, next)
    else weights.value.push(next)
    profile.value = { ...profile.value, weightKg: next.kg }
    notifyDemo()
  }

  function deleteWeight(id: string) {
    weights.value = weights.value.filter((item) => item.id !== id)
    notifyDemo()
  }

  function stamp(): string {
    return new Date().toISOString()
  }

  function pushMessage(message: ChatMessage) {
    messages.value.push(message)
  }

  function lastMealLabel(): { id: string; date: string; label: string } | null {
    const list = mealsOn(selectedDate.value)
    const meal = list[list.length - 1]
    if (!meal) return null
    return {
      id: meal.id,
      date: meal.date,
      label: meal.items.map((item) => localizedName(item, locale.value)).join(', '),
    }
  }

  function proposalFromIntent(text: string): { text: string; proposal?: Proposal; retryText?: string } {
    const intent = interpret(text)
    if (intent.type === 'unavailable') {
      if (!unavailablePending) {
        unavailablePending = true
        return { text: t('chat.unavailable'), retryText: text }
      }
      unavailablePending = false
      const recovered = interpret('La pranz am mancat 200 g piept de pui')
      return {
        text: t('chat.recovered'),
        proposal: recovered.type === 'meal' ? mealProposal(recovered.meal, false) : undefined,
      }
    }
    if (intent.type === 'not-found') return { text: t('chat.notFound', { query: intent.query }) }
    if (intent.type === 'ambiguous') return { text: t('chat.ambiguous') }
    if (intent.type === 'yesterday') {
      const day = nutrition(YESTERDAY)
      const names = mealsOn(YESTERDAY).flatMap((meal) => meal.items.map((item) => localizedName(item, locale.value)))
      const summary = t('chat.yesterday', {
        date: formatLongDate(YESTERDAY, locale.value),
        kcal: formatKcal(day.intake, locale.value).replace(' kcal', ''),
        target: String(profile.value.calorieTarget ?? 0),
      })
      const extra = names.length ? t('chat.yesterdayMeals', { meals: names.join(', ') }) : t('chat.yesterdayEmpty')
      return { text: `${summary} ${extra}` }
    }
    if (intent.type === 'delete') {
      const target = lastMealLabel()
      if (!target) return { text: t('chat.nothingToDelete') }
      const proposal: Proposal = {
        id: uid('proposal'),
        kind: 'delete-meal',
        status: 'pending',
        mealId: target.id,
        date: target.date,
        label: target.label,
      }
      return { text: t('chat.deleteAsk', { label: target.label }), proposal }
    }
    if (intent.type === 'weight') {
      const proposal: Proposal = {
        id: uid('proposal'),
        kind: 'weight',
        status: 'pending',
        kg: intent.kg,
        date: selectedDate.value,
        notes: '',
      }
      return { text: t('chat.weightIntro'), proposal }
    }
    if (intent.type === 'activity') {
      const proposal: Proposal = {
        id: uid('proposal'),
        kind: 'activity',
        status: 'pending',
        activity: {
          date: selectedDate.value,
          name: intent.name,
          nameEn: intent.nameEn,
          minutes: intent.minutes,
          intensity: intent.intensity,
          calories: intent.calories,
          notes: '',
        },
      }
      return { text: t('chat.activityIntro'), proposal }
    }
    return { text: t('chat.found'), proposal: mealProposal(intent.meal, intent.portionAssumption) }
  }

  function mealProposal(body: Omit<Meal, 'id' | 'date'>, portion: boolean): MealProposal {
    return {
      id: uid('proposal'),
      kind: 'meal',
      status: 'pending',
      corrected: false,
      assumptionsKey: portion ? 'chat.assumptionPortion' : 'chat.assumptionPrep',
      uncertaintyKey: portion ? 'chat.uncertaintyPortion' : null,
      sourcesKey: 'chat.sourceValue',
      meal: {
        id: uid('meal'),
        date: selectedDate.value,
        type: body.type,
        time: body.time,
        items: body.items.map((item) => ({ ...item, id: uid('food') })),
      },
    }
  }

  async function sendChat(text: string, thinkMs = 800) {
    const trimmed = text.trim()
    if (!trimmed || chatThinking.value) return
    pushMessage({ id: uid('msg'), role: 'user', text: trimmed, at: stamp() })
    chatThinking.value = true
    await delay(thinkMs)
    const reply = proposalFromIntent(trimmed)
    pushMessage({
      id: uid('msg'),
      role: 'assistant',
      text: reply.text,
      at: stamp(),
      proposal: reply.proposal,
      retryText: reply.retryText,
    })
    chatThinking.value = false
  }

  async function retryChat(text: string) {
    unavailablePending = false
    await sendChat(text)
  }

  function updateProposal(id: string, recipe: (proposal: Proposal) => Proposal) {
    const message = messages.value.find((item) => item.proposal?.id === id)
    if (!message?.proposal || message.proposal.status !== 'pending') return
    message.proposal = recipe(message.proposal)
  }

  function confirmProposal(id: string) {
    const message = messages.value.find((item) => item.proposal?.id === id)
    const proposal = message?.proposal
    if (!proposal || proposal.status !== 'pending') return
    if (proposal.kind === 'meal') {
      upsertMeal(proposal.meal)
      proposal.status = 'confirmed'
      message.text = t('chat.saved')
      return
    }
    if (proposal.kind === 'weight') {
      upsertWeight({ id: uid('wt'), date: proposal.date, kg: proposal.kg, notes: proposal.notes })
      proposal.status = 'confirmed'
      message.text = t('chat.weightSaved')
      return
    }
    if (proposal.kind === 'activity') {
      upsertActivity({ ...proposal.activity, id: uid('act') })
      proposal.status = 'confirmed'
      message.text = t('chat.activitySaved')
      return
    }
    deleteMeal(proposal.mealId, proposal.date)
    proposal.status = 'confirmed'
    message.text = t('chat.deleted')
  }

  function cancelProposal(id: string) {
    const message = messages.value.find((item) => item.proposal?.id === id)
    if (!message?.proposal || message.proposal.status !== 'pending') return
    message.proposal.status = 'cancelled'
    message.text = t('chat.cancelled')
  }

  function correctMealProposal(id: string, meal: Meal) {
    updateProposal(id, (proposal) => {
      if (proposal.kind !== 'meal') return proposal
      return { ...proposal, corrected: true, meal, uncertaintyKey: 'chat.uncertaintyPortion' }
    })
    const message = messages.value.find((item) => item.proposal?.id === id)
    if (message) message.text = t('chat.stillOpen')
    notifyDemo()
  }

  function correctWeightProposal(id: string, kg: number, notes: string) {
    updateProposal(id, (proposal) => (proposal.kind === 'weight' ? { ...proposal, kg, notes } : proposal))
    const message = messages.value.find((item) => item.proposal?.id === id)
    if (message) message.text = t('chat.stillOpen')
  }

  function correctActivityProposal(id: string, activity: ActivityProposal['activity']) {
    updateProposal(id, (proposal) => (proposal.kind === 'activity' ? { ...proposal, activity } : proposal))
    const message = messages.value.find((item) => item.proposal?.id === id)
    if (message) message.text = t('chat.stillOpen')
  }

  function showExample(kind: ExampleKind) {
    if (kind === 'empty') {
      messages.value = []
      chatThinking.value = false
      return
    }
    if (kind === 'processing') {
      void sendChat('Adaugă micul dejun', 2200)
      return
    }
    if (kind === 'meal') {
      void sendChat('La prânz am mâncat 200 g piept de pui, 200 g orez cu legume și un măr')
      return
    }
    if (kind === 'weight') {
      void sendChat('Înregistrează greutatea 84,6')
      return
    }
    if (kind === 'activity') {
      void sendChat('Am făcut sport 35 minute')
      return
    }
    if (kind === 'ambiguous') {
      void sendChat('am mâncat ceva')
      return
    }
    if (kind === 'missing') {
      void sendChat('adaugă zorbix')
      return
    }
    if (kind === 'down') {
      unavailablePending = false
      void sendChat('serviciu indisponibil')
      return
    }
    if (kind === 'delete') {
      void sendChat('șterge ultima masă')
      return
    }
    if (kind === 'saved') {
      pushMessage({ id: uid('msg'), role: 'assistant', text: t('chat.saved'), at: stamp() })
      return
    }
    const sample = interpret('La pranz am mancat 200 g piept de pui')
    const proposal = mealProposal(sample.type === 'meal' ? sample.meal : { type: 'lunch', time: '13:40', items: [] }, false)
    proposal.corrected = true
    pushMessage({
      id: uid('msg'),
      role: 'assistant',
      text: t('chat.stillOpen'),
      at: stamp(),
      proposal,
    })
  }

  const pendingMeals = computed(() =>
    messages.value
      .map((message) => message.proposal)
      .filter((proposal): proposal is MealProposal => proposal?.kind === 'meal' && proposal.status === 'pending'),
  )

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
    today: TODAY,
    todayNutrition,
    exerciseCalories,
    latestWeight,
    mealsOn,
    nutrition,
    setDate,
    showError,
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
    showExample,
    pendingMeals,
    mealCalories,
    notifyDemo,
  }
})
