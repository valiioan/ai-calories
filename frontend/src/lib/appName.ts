import { ref } from 'vue'
import { apiJson } from '@/lib/api'

export const appName = ref('CalorAI')

export async function loadAppName(): Promise<void> {
  try {
    const data = await apiJson<{ name?: unknown }>('/api/app')
    const name = typeof data.name === 'string' ? data.name.trim() : ''
    if (name) appName.value = name
  } catch {
    // Numele din .env lipsește. Rămâne valoarea implicită.
  }
}
