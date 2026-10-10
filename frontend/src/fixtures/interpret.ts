import { addDays, TODAY } from '@/lib/dates'
import type { FoodItem, Meal, MealType } from '@/types/domain'

export type Intent =
  | { type: 'meal'; meal: Omit<Meal, 'id' | 'date'>; portionAssumption: boolean }
  | { type: 'weight'; kg: number }
  | { type: 'activity'; name: string; nameEn: string; minutes: number; intensity: 'low' | 'moderate' | 'high'; calories: number }
  | { type: 'yesterday' }
  | { type: 'ambiguous' }
  | { type: 'not-found'; query: string }
  | { type: 'unavailable' }
  | { type: 'delete' }

function normalize(value: string): string {
  return value.normalize('NFD').replace(/\p{M}/gu, '').toLowerCase()
}

function food(partial: Omit<FoodItem, 'id'>): FoodItem {
  return { id: 'draft', ...partial }
}

const breakfast: Omit<Meal, 'id' | 'date'> = {
  type: 'breakfast',
  time: '08:15',
  items: [
    food({ name: 'Iaurt', nameEn: 'Yogurt', quantity: 150, unit: 'g', calories: 90, protein: 8, carbs: 6, fat: 3 }),
    food({ name: 'Ovăz', nameEn: 'Oats', quantity: 40, unit: 'g', calories: 150, protein: 5, carbs: 27, fat: 3 }),
    food({ name: 'Banană', nameEn: 'Banana', quantity: 1, unit: 'piece', calories: 190, protein: 2, carbs: 24, fat: 0 }),
  ],
}

const lunch: Omit<Meal, 'id' | 'date'> = {
  type: 'lunch',
  time: '13:40',
  items: [
    food({ name: 'Piept de pui', nameEn: 'Chicken breast', quantity: 200, unit: 'g', calories: 330, protein: 50, carbs: 0, fat: 7 }),
    food({ name: 'Orez cu legume', nameEn: 'Rice with vegetables', quantity: 200, unit: 'g', calories: 260, protein: 6, carbs: 48, fat: 4 }),
    food({ name: 'Măr', nameEn: 'Apple', quantity: 1, unit: 'piece', calories: 95, protein: 0, carbs: 25, fat: 0 }),
  ],
}

const dinner: Omit<Meal, 'id' | 'date'> = {
  type: 'dinner',
  time: '19:30',
  items: [
    food({ name: 'Salată', nameEn: 'Salad', quantity: 180, unit: 'g', calories: 90, protein: 3, carbs: 8, fat: 4 }),
    food({ name: 'Brânză', nameEn: 'Cheese', quantity: 40, unit: 'g', calories: 160, protein: 10, carbs: 1, fat: 12 }),
  ],
}

const snack: Omit<Meal, 'id' | 'date'> = {
  type: 'snack',
  time: '16:00',
  items: [
    food({ name: 'Măr', nameEn: 'Apple', quantity: 1, unit: 'piece', calories: 95, protein: 0, carbs: 25, fat: 0 }),
    food({ name: 'Iaurt', nameEn: 'Yogurt', quantity: 100, unit: 'g', calories: 70, protein: 6, carbs: 5, fat: 2 }),
  ],
}

function mealFor(type: MealType): Omit<Meal, 'id' | 'date'> {
  if (type === 'breakfast') return breakfast
  if (type === 'lunch') return lunch
  if (type === 'dinner') return dinner
  return snack
}

function numberIn(text: string, fallback: number): number {
  const match = text.match(/\d+(?:[.,]\d+)?/)
  if (!match) return fallback
  return Number(match[0].replace(',', '.'))
}

export function interpret(text: string): Intent {
  const value = normalize(text)
  if (value.includes('serviciu') || value.includes('service') || value.includes('indisponibil') || value.includes('unavailable')) {
    return { type: 'unavailable' }
  }
  if (value.includes('zorbix') || value.includes('inexistent') || value.includes('not found') || value.includes('negasit')) {
    return { type: 'not-found', query: text.trim() }
  }
  if (value.includes('sterge') || value.includes('delete')) return { type: 'delete' }
  if (value.includes('greutat') || value.includes('weight') || value.includes('cantar') || value.includes('cantarit')) {
    return { type: 'weight', kg: Math.round(numberIn(value, 84.2) * 10) / 10 }
  }
  if (value.includes('sport') || value.includes('alerg') || value.includes('sala') || value.includes('workout') || value.includes('exercise') || value.includes('mers')) {
    const minutes = Math.round(numberIn(value, 30))
    return {
      type: 'activity',
      name: 'Mers alert',
      nameEn: 'Brisk walk',
      minutes,
      intensity: minutes >= 45 ? 'high' : 'moderate',
      calories: minutes * 5,
    }
  }
  if (value.includes('ieri') || value.includes('yesterday')) return { type: 'yesterday' }
  if (value.includes('ceva') || value.includes('asta') || value.includes('something') || value.includes('whatever')) {
    return { type: 'ambiguous' }
  }

  let type: MealType = 'lunch'
  if (value.includes('mic dejun') || value.includes('micul dejun') || value.includes('breakfast')) type = 'breakfast'
  else if (value.includes('cina') || value.includes('dinner')) type = 'dinner'
  else if (value.includes('gustar') || value.includes('snack')) type = 'snack'
  else if (value.includes('pranz') || value.includes('lunch') || value.includes('pui') || value.includes('chicken')) type = 'lunch'
  else if (value.includes('mancat') || value.includes('ate') || value.includes('adauga') || value.includes('add ')) type = 'lunch'
  else if (value.trim().length < 12) return { type: 'ambiguous' }

  const hasAmount = /\d/.test(value)
  return { type: 'meal', meal: mealFor(type), portionAssumption: !hasAmount }
}

export const YESTERDAY = addDays(TODAY, -1)
