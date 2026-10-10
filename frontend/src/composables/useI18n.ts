import { useAppStore } from '@/stores/app'
import { translate, type MessageKey } from '@/i18n'

export function useI18n() {
  const app = useAppStore()
  function t(key: MessageKey, params?: Record<string, string | number>) {
    return translate(app.locale, key, params)
  }
  return { t }
}
