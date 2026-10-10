import { createPinia } from 'pinia'
import { createApp } from 'vue'
import App from './App.vue'
import { loadAppName } from '@/lib/appName'
import { router } from './router'
import 'vue-sonner/style.css'
import './style.css'

await loadAppName()

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.mount('#app')
