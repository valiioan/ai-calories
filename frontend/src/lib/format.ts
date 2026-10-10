import type { Locale, Units } from '@/types/domain'
import { parseIso } from '@/lib/dates'

const tag = (locale: Locale) => (locale === 'en' ? 'en-GB' : 'ro-RO')

export function formatNumber(value: number, locale: Locale, digits = 0): string {
  return new Intl.NumberFormat(tag(locale), {
    maximumFractionDigits: digits,
    minimumFractionDigits: digits,
  }).format(value)
}

export function formatKcal(value: number, locale: Locale): string {
  return `${formatNumber(value, locale)} kcal`
}

export function formatWeight(kg: number, units: Units, locale: Locale): string {
  if (units === 'imperial') {
    return `${formatNumber(kg * 2.2046226218, locale, 1)} lb`
  }
  return `${formatNumber(kg, locale, 1)} kg`
}

export function formatHeight(cm: number, units: Units, locale: Locale): string {
  if (units === 'imperial') {
    return `${formatNumber(cm / 2.54, locale, 0)} in`
  }
  return `${formatNumber(cm, locale, 0)} cm`
}

export function formatLongDate(iso: string, locale: Locale): string {
  return new Intl.DateTimeFormat(tag(locale), {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
  }).format(parseIso(iso))
}

export function formatShortDate(iso: string, locale: Locale): string {
  return new Intl.DateTimeFormat(tag(locale), {
    day: 'numeric',
    month: 'short',
  }).format(parseIso(iso))
}

export function formatWeekday(iso: string, locale: Locale): string {
  return new Intl.DateTimeFormat(tag(locale), { weekday: 'short' }).format(parseIso(iso))
}

export function formatMonth(iso: string, locale: Locale): string {
  return new Intl.DateTimeFormat(tag(locale), { month: 'long', year: 'numeric' }).format(parseIso(iso))
}

export function formatTime(isoDateTime: string, locale: Locale): string {
  return new Intl.DateTimeFormat(tag(locale), { hour: '2-digit', minute: '2-digit' }).format(new Date(isoDateTime))
}

export function initials(name: string): string {
  const parts = name.trim().split(/\s+/).filter(Boolean)
  const letters = `${parts[0]?.[0] ?? ''}${parts[1]?.[0] ?? ''}`
  return letters.toUpperCase() || 'C'
}

export function firstName(name: string): string {
  return name.trim().split(/\s+/)[0] || name
}

export function localizedName(item: { name: string; nameEn?: string }, locale: Locale): string {
  return locale === 'en' && item.nameEn ? item.nameEn : item.name
}
