export const TODAY = '2026-10-10'

export function toIso(date: Date): string {
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

export function parseIso(iso: string): Date {
  const [y, m, d] = iso.split('-').map(Number)
  return new Date(y, (m ?? 1) - 1, d ?? 1)
}

export function addDays(iso: string, days: number): string {
  const date = parseIso(iso)
  date.setDate(date.getDate() + days)
  return toIso(date)
}

export function compareIso(a: string, b: string): number {
  return a < b ? -1 : a > b ? 1 : 0
}

export function eachDay(start: string, end: string): string[] {
  if (start > end) return []
  const days: string[] = []
  let cursor = start
  while (cursor <= end) {
    days.push(cursor)
    cursor = addDays(cursor, 1)
  }
  return days
}

export function calendarCells(monthIso: string): { iso: string; inMonth: boolean }[] {
  const [y, m] = monthIso.split('-').map(Number)
  const first = new Date(y, (m ?? 1) - 1, 1)
  const offset = (first.getDay() + 6) % 7
  const start = new Date(y, (m ?? 1) - 1, 1 - offset)
  return Array.from({ length: 42 }, (_, index) => {
    const date = new Date(start)
    date.setDate(start.getDate() + index)
    return { iso: toIso(date), inMonth: date.getMonth() === (m ?? 1) - 1 }
  })
}

export function monthIso(iso: string): string {
  return `${iso.slice(0, 7)}-01`
}

export function shiftMonth(month: string, delta: number): string {
  const date = parseIso(month)
  date.setMonth(date.getMonth() + delta)
  return toIso(new Date(date.getFullYear(), date.getMonth(), 1))
}
