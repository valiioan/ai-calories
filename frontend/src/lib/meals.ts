import type { FoodUnit, Meal, MealType } from '@/types/domain'

export const MEAL_TYPES: MealType[] = ['breakfast', 'lunch', 'dinner', 'snack']

export function blankMeal(date: string, type: MealType = 'breakfast'): Meal {
  return {
    id: '',
    date,
    type,
    time: '12:00',
    items: [
      {
        id: '',
        name: '',
        quantity: 100,
        unit: 'g',
        calories: 0,
        protein: null,
        carbs: null,
        fat: null,
        source: 'manual',
      },
    ],
  }
}

export function mealCalories(meal: Meal): number {
  return meal.items.reduce((sum, food) => sum + food.calories, 0)
}

export function sumMacro(meal: Meal, key: 'protein' | 'carbs' | 'fat'): { total: number; complete: boolean } {
  let total = 0
  let complete = true
  for (const food of meal.items) {
    const value = food[key]
    if (value == null) complete = false
    else total += value
  }
  return { total, complete }
}

export function toApiUnit(unit: FoodUnit): 'g' | 'ml' | 'serving' {
  return unit === 'piece' ? 'serving' : unit
}

export function fromApiUnit(unit: string): FoodUnit {
  return unit === 'serving' ? 'piece' : unit === 'ml' ? 'ml' : 'g'
}
