import { addDays, TODAY } from '@/lib/dates'
import type { Activity, DayBaseline, FoodItem, Meal, MealType, Profile, WeightPoint } from '@/types/domain'

function mulberry32(seed: number) {
  let state = seed
  return () => {
    state |= 0
    state = (state + 0x6d2b79f5) | 0
    let t = Math.imul(state ^ (state >>> 15), 1 | state)
    t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t
    return ((t ^ (t >>> 14)) >>> 0) / 4294967296
  }
}

export function buildBaselines(): Record<string, DayBaseline> {
  const random = mulberry32(20261010)
  const days: Record<string, DayBaseline> = {}
  for (let offset = -364; offset <= 0; offset += 1) {
    const date = addDays(TODAY, offset)
    const logged = random() > 0.18
    days[date] = {
      intake: logged ? Math.round(1500 + random() * 900) : 0,
      expenditure: Math.round(2200 + random() * 500),
      protein: logged ? Math.round(70 + random() * 60) : 0,
      carbs: logged ? Math.round(140 + random() * 140) : 0,
      fat: logged ? Math.round(40 + random() * 45) : 0,
      logged,
    }
  }
  const emptyDay = addDays(TODAY, -2)
  days[emptyDay] = { intake: 0, expenditure: 2380, protein: 0, carbs: 0, fat: 0, logged: false }
  days[TODAY] = { intake: 1580, expenditure: 2450, protein: 98, carbs: 186, fat: 48, logged: true }
  return days
}

function item(partial: FoodItem): FoodItem {
  return partial
}

function meal(partial: Meal): Meal {
  return partial
}

export function initialMeals(): Record<string, Meal[]> {
  const yesterday = addDays(TODAY, -1)
  return {
    [TODAY]: [
      meal({
        id: 'meal-breakfast',
        date: TODAY,
        type: 'breakfast',
        time: '08:10',
        items: [
          item({ id: 'f1', name: 'Iaurt', nameEn: 'Yogurt', quantity: 150, unit: 'g', calories: 90, protein: 8, carbs: 6, fat: 3 }),
          item({ id: 'f2', name: 'Ovăz', nameEn: 'Oats', quantity: 40, unit: 'g', calories: 150, protein: 5, carbs: 27, fat: 3 }),
          item({ id: 'f3', name: 'Banană', nameEn: 'Banana', quantity: 1, unit: 'piece', calories: 190, protein: 2, carbs: 24, fat: 0 }),
        ],
      }),
      meal({
        id: 'meal-lunch',
        date: TODAY,
        type: 'lunch',
        time: '13:40',
        items: [
          item({ id: 'f4', name: 'Piept de pui', nameEn: 'Chicken breast', quantity: 200, unit: 'g', calories: 330, protein: 50, carbs: 0, fat: 7 }),
          item({ id: 'f5', name: 'Orez cu legume', nameEn: 'Rice with vegetables', quantity: 200, unit: 'g', calories: 260, protein: 6, carbs: 48, fat: 4 }),
          item({ id: 'f6', name: 'Măr', nameEn: 'Apple', quantity: 1, unit: 'piece', calories: 95, protein: 0, carbs: 25, fat: 0 }),
        ],
      }),
      meal({
        id: 'meal-snack',
        date: TODAY,
        type: 'snack',
        time: '16:20',
        items: [
          item({ id: 'f7', name: 'Cafea', nameEn: 'Coffee', quantity: 200, unit: 'ml', calories: 15, protein: 1, carbs: 1, fat: 0 }),
          item({ id: 'f8', name: 'Biscuiți', nameEn: 'Biscuits', quantity: 30, unit: 'g', calories: 150, protein: 2, carbs: 18, fat: 7 }),
        ],
      }),
      meal({
        id: 'meal-dinner',
        date: TODAY,
        type: 'dinner',
        time: '19:30',
        items: [
          item({ id: 'f9', name: 'Salată', nameEn: 'Salad', quantity: 150, unit: 'g', calories: 80, protein: 2, carbs: 6, fat: 3 }),
          item({ id: 'f10', name: 'Brânză', nameEn: 'Cheese', quantity: 40, unit: 'g', calories: 220, protein: 14, carbs: 1, fat: 18 }),
        ],
      }),
    ],
    [yesterday]: [
      meal({
        id: 'meal-y-breakfast',
        date: yesterday,
        type: 'breakfast',
        time: '08:00',
        items: [
          item({ id: 'y1', name: 'Omletă', nameEn: 'Omelette', quantity: 2, unit: 'piece', calories: 280, protein: 18, carbs: 2, fat: 20 }),
          item({ id: 'y2', name: 'Pâine', nameEn: 'Bread', quantity: 40, unit: 'g', calories: 110, protein: 4, carbs: 20, fat: 1 }),
        ],
      }),
      meal({
        id: 'meal-y-lunch',
        date: yesterday,
        type: 'lunch',
        time: '13:10',
        items: [
          item({ id: 'y3', name: 'Supă de legume', nameEn: 'Vegetable soup', quantity: 350, unit: 'ml', calories: 180, protein: 6, carbs: 22, fat: 6 }),
          item({ id: 'y4', name: 'Pâine prăjită', nameEn: 'Toast', quantity: 30, unit: 'g', calories: 90, protein: 3, carbs: 16, fat: 1 }),
        ],
      }),
    ],
  }
}

export function initialActivities(): Activity[] {
  return [
    {
      id: 'act-today',
      date: TODAY,
      name: 'Mers alert',
      nameEn: 'Brisk walk',
      minutes: 35,
      intensity: 'moderate',
      calories: 180,
      notes: '',
    },
    {
      id: 'act-yesterday',
      date: addDays(TODAY, -1),
      name: 'Bicicletă',
      nameEn: 'Cycling',
      minutes: 20,
      intensity: 'low',
      calories: 110,
      notes: '',
    },
  ]
}

export function initialWeights(): WeightPoint[] {
  const points: WeightPoint[] = []
  let kg = 86.4
  for (let offset = -360; offset <= 0; offset += 3) {
    const date = addDays(TODAY, offset)
    const wave = ((Math.abs(offset) % 9) - 4) * 0.03
    kg = Math.round((kg - 0.02 + wave) * 10) / 10
    points.push({ id: `wt-${date}`, date, kg, notes: '' })
  }
  points[points.length - 1] = {
    id: 'wt-today',
    date: TODAY,
    kg: 84.6,
    notes: 'Dimineața, înainte de masă',
  }
  return points
}

export function demoProfile(): Profile {
  return {
    firstName: 'Ana',
    lastName: 'Ungureanu',
    email: 'ana@calor.ai',
    dob: '1994-04-12',
    heightCm: 168,
    weightKg: 84.6,
    targetWeightKg: 80,
    activityLevel: 'light',
    goal: 'lose',
    calorieTarget: 2200,
    units: 'metric',
    timezone: 'Europe/Bucharest',
  }
}

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
      },
    ],
  }
}

export const MEAL_TYPES: MealType[] = ['breakfast', 'lunch', 'dinner', 'snack']

export function mealCalories(mealItem: Meal): number {
  return mealItem.items.reduce((sum, food) => sum + food.calories, 0)
}

export function sumMacro(mealItem: Meal, key: 'protein' | 'carbs' | 'fat'): { total: number; complete: boolean } {
  let total = 0
  let complete = true
  for (const food of mealItem.items) {
    const value = food[key]
    if (value == null) complete = false
    else total += value
  }
  return { total, complete }
}
