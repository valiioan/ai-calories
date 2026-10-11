import { fromApiUnit, toApiUnit } from '@/lib/meals'
import type { Activity, ChatMessage, DayBaseline, FoodItem, Meal, MealType, Proposal, WeightPoint } from '@/types/domain'

export type ApiMealItem = {
  id: number
  food_id: number | null
  custom_name: string | null
  quantity: number
  unit: string
  energy_kcal: number | null
  protein_g: number | null
  carbohydrate_g: number | null
  fat_g: number | null
  nutrition_source: 'usda' | 'manual'
}

export type ApiMeal = {
  id: number
  date: string
  time: string
  meal_type: MealType
  note: string | null
  original_text: string | null
  assumptions: string | null
  items: ApiMealItem[]
}

export type ApiActivity = {
  id: number
  date: string
  name: string
  duration_minutes: number | null
  intensity: Activity['intensity'] | null
  energy_kcal: number
  note: string | null
}

export type ApiWeight = {
  id: number
  recorded_on: string
  weight_kg: number
  note: string | null
}

export type ApiSummary = {
  date: string
  energy_in_kcal: number
  energy_out_kcal: number
  protein_g: number
  carbohydrate_g: number
  fat_g: number
  logged: boolean
}

export type ApiProposal = {
  id: number
  kind: Proposal['kind']
  status: Proposal['status']
  target_id: number | null
  payload: Record<string, unknown>
}

export type ApiMessage = {
  id: number
  role: 'user' | 'assistant'
  content: string
  created_at: string
  proposal_id: number | null
  revision_id: number | null
  proposal: ApiProposal | null
}

function text(value: unknown): string | null {
  return typeof value === 'string' && value.trim() !== '' ? value : null
}

export function toFoodItem(item: ApiMealItem): FoodItem {
  return {
    id: String(item.id),
    foodId: item.food_id,
    name: item.custom_name ?? '',
    quantity: item.quantity,
    unit: fromApiUnit(item.unit),
    calories: item.energy_kcal ?? 0,
    protein: item.protein_g,
    carbs: item.carbohydrate_g,
    fat: item.fat_g,
    source: item.nutrition_source,
  }
}

export function toMeal(meal: ApiMeal): Meal {
  return {
    id: String(meal.id),
    date: meal.date,
    type: meal.meal_type,
    time: meal.time,
    items: meal.items.map(toFoodItem),
  }
}

export function mealPayload(meal: Meal) {
  return {
    eaten_on: meal.date,
    time: meal.time,
    meal_type: meal.type,
    note: null,
    original_text: null,
    assumptions: null,
    items: meal.items.map((item) => ({
      food_id: item.source === 'usda' ? item.foodId ?? null : null,
      custom_name: item.name,
      quantity: item.quantity,
      unit: toApiUnit(item.unit),
      energy_kcal: item.source === 'usda' ? null : item.calories,
      protein_g: item.source === 'usda' ? null : item.protein,
      carbohydrate_g: item.source === 'usda' ? null : item.carbs,
      fat_g: item.source === 'usda' ? null : item.fat,
      fiber_g: null,
    })),
  }
}

export function toActivity(entry: ApiActivity): Activity {
  return {
    id: String(entry.id),
    date: entry.date,
    name: entry.name,
    minutes: entry.duration_minutes ?? 0,
    intensity: entry.intensity ?? 'moderate',
    calories: entry.energy_kcal,
    notes: entry.note ?? '',
  }
}

export function activityPayload(activity: Activity) {
  return {
    performed_on: activity.date,
    time: '12:00',
    name: activity.name,
    duration_minutes: activity.minutes,
    intensity: activity.intensity,
    energy_kcal: activity.calories,
    note: activity.notes || null,
  }
}

export function toWeight(entry: ApiWeight): WeightPoint {
  return {
    id: String(entry.id),
    date: entry.recorded_on,
    kg: entry.weight_kg,
    notes: entry.note ?? '',
  }
}

export function toBaseline(row: ApiSummary): DayBaseline {
  return {
    intake: row.energy_in_kcal,
    expenditure: row.energy_out_kcal,
    protein: row.protein_g,
    carbs: row.carbohydrate_g,
    fat: row.fat_g,
    logged: row.logged,
  }
}

function payloadMeal(payload: Record<string, unknown>, id = ''): Meal {
  const items = Array.isArray(payload.items) ? payload.items : []
  return {
    id,
    date: String(payload.eaten_on ?? ''),
    type: (payload.meal_type as MealType) ?? 'snack',
    time: String(payload.time ?? '12:00'),
    items: items.map((item, index) => {
      const row = item as ApiMealItem
      return toFoodItem({
        ...row,
        id: index + 1,
        food_id: row.food_id ?? null,
        custom_name: row.custom_name ?? null,
        nutrition_source: row.nutrition_source ?? 'usda',
      })
    }),
  }
}

export function toProposal(proposal: ApiProposal): Proposal {
  const payload = proposal.payload
  if (proposal.kind === 'weight') {
    return {
      id: String(proposal.id),
      kind: 'weight',
      status: proposal.status,
      kg: Number(payload.weight_kg ?? 0),
      date: String(payload.recorded_on ?? ''),
      notes: text(payload.note) ?? '',
    }
  }
  if (proposal.kind === 'activity') {
    return {
      id: String(proposal.id),
      kind: 'activity',
      status: proposal.status,
      activity: {
        date: String(payload.performed_on ?? ''),
        name: String(payload.name ?? ''),
        minutes: Number(payload.duration_minutes ?? 0),
        intensity: (payload.intensity as Activity['intensity']) ?? 'moderate',
        calories: Number(payload.energy_kcal ?? 0),
        notes: text(payload.note) ?? '',
      },
    }
  }
  if (proposal.kind === 'delete-meal') {
    return {
      id: String(proposal.id),
      kind: 'delete-meal',
      status: proposal.status,
      mealId: String(proposal.target_id ?? ''),
      date: String(payload.eaten_on ?? ''),
      label: String(payload.label ?? ''),
    }
  }
  const meal = payloadMeal(payload, String(proposal.target_id ?? ''))
  const sources = new Set(meal.items.map((item) => item.source))
  return {
    id: String(proposal.id),
    kind: 'meal',
    status: proposal.status,
    corrected: Boolean(payload.corrected),
    meal,
    assumptions: text(payload.assumptions),
    uncertainty: text(payload.uncertainty),
    source: sources.has('usda') ? 'USDA FoodData Central' : 'manual',
  }
}

export function toChatMessage(message: ApiMessage): ChatMessage {
  return {
    id: String(message.id),
    role: message.role,
    text: message.content,
    at: message.created_at,
    proposal: message.proposal ? toProposal(message.proposal) : undefined,
    revisionId: message.revision_id ? String(message.revision_id) : undefined,
  }
}

export function proposalPatch(proposal: Proposal): Record<string, unknown> {
  if (proposal.kind === 'weight') {
    return { recorded_on: proposal.date, weight_kg: proposal.kg, note: proposal.notes || null }
  }
  if (proposal.kind === 'activity') {
    return {
      performed_on: proposal.activity.date,
      name: proposal.activity.name,
      duration_minutes: proposal.activity.minutes,
      intensity: proposal.activity.intensity,
      energy_kcal: proposal.activity.calories,
      note: proposal.activity.notes || null,
    }
  }
  if (proposal.kind !== 'meal') return {}
  return {
    eaten_on: proposal.meal.date,
    time: proposal.meal.time,
    meal_type: proposal.meal.type,
    assumptions: proposal.assumptions,
    items: proposal.meal.items.map((item) => ({
      food_id: item.source === 'manual' ? null : item.foodId ?? null,
      custom_name: item.name,
      display_name: item.name,
      quantity: item.quantity,
      unit: toApiUnit(item.unit),
      nutrition_source: item.source ?? 'manual',
      energy_kcal: item.source === 'usda' ? null : item.calories,
      protein_g: item.protein,
      carbohydrate_g: item.carbs,
      fat_g: item.fat,
    })),
  }
}

export function unwrap<T>(payload: T[] | { data: T[] }): T[] {
  return Array.isArray(payload) ? payload : payload.data
}

export function emptyDay(): DayBaseline {
  return { intake: 0, expenditure: 0, protein: 0, carbs: 0, fat: 0, logged: false }
}
