import { createRouter, createWebHistory } from 'vue-router'
import { translate, type MessageKey } from '@/i18n'
import { appName } from '@/lib/appName'
import { useAppStore } from '@/stores/app'

export const router = createRouter({
  history: createWebHistory(),
  scrollBehavior() {
    return { top: 0 }
  },
  routes: [
    { path: '/login', name: 'login', component: () => import('@/views/auth/LoginView.vue'), meta: { guest: true, title: 'auth.loginTitle' } },
    { path: '/register', name: 'register', component: () => import('@/views/auth/RegisterView.vue'), meta: { guest: true, title: 'auth.registerTitle' } },
    { path: '/forgot-password', name: 'forgot', component: () => import('@/views/auth/ForgotPasswordView.vue'), meta: { guest: true, title: 'auth.forgotTitle' } },
    { path: '/reset-password', name: 'reset', component: () => import('@/views/auth/ResetPasswordView.vue'), meta: { guest: true, title: 'auth.resetTitle' } },
    { path: '/onboarding', name: 'onboarding', component: () => import('@/views/onboarding/OnboardingView.vue'), meta: { auth: true, title: 'onboarding.pageTitle' } },
    {
      path: '/',
      component: () => import('@/components/layout/AppShell.vue'),
      meta: { shell: true },
      children: [
        { path: '', name: 'dashboard', component: () => import('@/views/DashboardView.vue'), meta: { title: 'dashboard.today' } },
        { path: 'jurnal', name: 'journal', component: () => import('@/views/JournalView.vue'), meta: { title: 'nav.journal' } },
        { path: 'chat', name: 'chat', component: () => import('@/views/ChatView.vue'), meta: { title: 'chat.title' } },
        { path: 'activitati', name: 'activities', component: () => import('@/views/ActivitiesView.vue'), meta: { title: 'activity.title' } },
        { path: 'greutate', name: 'weight', component: () => import('@/views/WeightView.vue'), meta: { title: 'weight.title' } },
        { path: 'progres', name: 'progress', component: () => import('@/views/ProgressView.vue'), meta: { title: 'reports.title' } },
        { path: 'setari', name: 'settings', component: () => import('@/views/SettingsView.vue'), meta: { title: 'settings.title' } },
        { path: 'profil', name: 'profile', component: () => import('@/views/ProfileView.vue'), meta: { title: 'profile.title' } },
      ],
    },
    { path: '/:pathMatch(.*)*', redirect: '/' },
  ],
})

router.beforeEach(async (to) => {
  const app = useAppStore()
  await app.boot()
  const guest = to.matched.some((record) => record.meta.guest)
  const needsAuth = to.matched.some((record) => record.meta.shell || record.meta.auth)
  if (guest) {
    if (app.user && to.name !== 'reset') return { name: app.onboarded ? 'dashboard' : 'onboarding' }
    return
  }
  if (!needsAuth) return
  if (!app.user) return { name: 'login' }
  if (!app.onboarded && to.name !== 'onboarding') return { name: 'onboarding' }
  if (app.onboarded && to.name === 'onboarding') return { name: 'dashboard' }
})

router.afterEach((to) => {
  const app = useAppStore()
  const title = to.meta.title
  if (typeof title === 'string') {
    document.title = `${translate(app.locale, title as MessageKey)} · ${appName.value}`
  }
})
