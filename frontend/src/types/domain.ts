export type Locale = 'ro' | 'en'
export type Units = 'metric' | 'imperial'
export type Goal = 'lose' | 'maintain' | 'gain'
export type ActivityLevel = 'sedentary' | 'light' | 'moderate' | 'active' | 'very_active'
export type Appearance = 'light' | 'dark' | 'system'
export type MealType = 'breakfast' | 'lunch' | 'dinner' | 'snack'
export type Intensity = 'low' | 'moderate' | 'high'
export type FoodUnit = 'g' | 'ml' | 'piece'
export type ViewStatus = 'ready' | 'loading' | 'error'
export type Period = '7' | '30' | '90' | 'custom'
export type WeightRange = '7d' | '30d' | '90d' | '1y'

export type FoodItem = {
  id: string
  name: string
  nameEn?: string
  quantity: number
  unit: FoodUnit
  calories: number
  protein: number | null
  carbs: number | null
  fat: number | null
}

export type Meal = {
  id: string
  date: string
  type: MealType
  time: string
  items: FoodItem[]
}

export type Activity = {
  id: string
  date: string
  name: string
  nameEn?: string
  minutes: number
  intensity: Intensity
  calories: number
  notes: string
}

export type WeightPoint = {
  id: string
  date: string
  kg: number
  notes: string
}

export type Profile = {
  firstName: string
  lastName: string
  email: string
  dob: string
  heightCm: number | null
  weightKg: number
  targetWeightKg: number | null
  activityLevel: ActivityLevel
  goal: Goal
  calorieTarget: number | null
  units: Units
  timezone: string
}

export type Notifications = {
  meals: boolean
  weight: boolean
  weekly: boolean
}

export type DayBaseline = {
  intake: number
  expenditure: number
  protein: number
  carbs: number
  fat: number
  logged: boolean
}

export type MealProposal = {
  id: string
  kind: 'meal'
  status: 'pending' | 'confirmed' | 'cancelled'
  corrected: boolean
  meal: Meal
  assumptionsKey: 'chat.assumptionPrep' | 'chat.assumptionPortion'
  uncertaintyKey: 'chat.uncertaintyPortion' | null
  sourcesKey: 'chat.sourceValue'
}

export type WeightProposal = {
  id: string
  kind: 'weight'
  status: 'pending' | 'confirmed' | 'cancelled'
  kg: number
  date: string
  notes: string
}

export type ActivityProposal = {
  id: string
  kind: 'activity'
  status: 'pending' | 'confirmed' | 'cancelled'
  activity: Omit<Activity, 'id'>
}

export type DeleteProposal = {
  id: string
  kind: 'delete-meal'
  status: 'pending' | 'confirmed' | 'cancelled'
  mealId: string
  date: string
  label: string
}

export type Proposal = MealProposal | WeightProposal | ActivityProposal | DeleteProposal

export type ChatMessage = {
  id: string
  role: 'user' | 'assistant'
  text: string
  at: string
  proposal?: Proposal
  retryText?: string
}

export type DemoUser = {
  id: number
  email: string
  firstName: string
  lastName: string
}

export type ExampleKind =
  | 'empty'
  | 'processing'
  | 'meal'
  | 'saved'
  | 'corrected'
  | 'delete'
  | 'weight'
  | 'activity'
  | 'ambiguous'
  | 'missing'
  | 'down'
