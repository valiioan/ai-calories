<?php

namespace App\Services\Agent;

class ToolCatalog
{
    /**
     * @return list<array<string, mixed>>
     */
    public function definitions(): array
    {
        $mealItem = [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'food_id' => ['type' => 'integer'],
                'quantity' => ['type' => 'number'],
                'unit' => ['type' => 'string', 'enum' => ['g', 'ml', 'serving']],
                'display_name' => ['type' => 'string'],
            ],
            'required' => ['food_id', 'quantity', 'unit', 'display_name'],
        ];

        return [
            $this->tool('search_food', 'Search USDA foods in English and return cached nutrition per 100 g.', [
                'query' => ['type' => 'string'],
                'page' => ['type' => 'integer'],
            ], ['query', 'page']),
            $this->tool('preview_meal', 'Build an unsaved meal estimate. Never invent calories; use food_id values from search_food.', [
                'eaten_on' => ['type' => 'string'],
                'time' => ['type' => 'string'],
                'meal_type' => ['type' => 'string', 'enum' => ['breakfast', 'lunch', 'dinner', 'snack']],
                'original_text' => ['type' => 'string'],
                'assumptions' => ['type' => ['string', 'null']],
                'uncertainty' => ['type' => ['string', 'null']],
                'items' => ['type' => 'array', 'items' => $mealItem],
            ], ['eaten_on', 'time', 'meal_type', 'original_text', 'assumptions', 'uncertainty', 'items']),
            $this->tool('preview_update', 'Propose a change to an existing meal id from the journal. Do not guess the id.', [
                'meal_id' => ['type' => 'integer'],
                'eaten_on' => ['type' => 'string'],
                'time' => ['type' => 'string'],
                'meal_type' => ['type' => 'string', 'enum' => ['breakfast', 'lunch', 'dinner', 'snack']],
                'original_text' => ['type' => 'string'],
                'assumptions' => ['type' => ['string', 'null']],
                'uncertainty' => ['type' => ['string', 'null']],
                'items' => ['type' => 'array', 'items' => $mealItem],
            ], ['meal_id', 'eaten_on', 'time', 'meal_type', 'original_text', 'assumptions', 'uncertainty', 'items']),
            $this->tool('preview_delete', 'Propose deleting one meal by its id. This does not delete it.', [
                'meal_id' => ['type' => 'integer'],
            ], ['meal_id']),
            $this->tool('preview_activity', 'Propose a physical activity. Calories are an estimate the user must confirm.', [
                'performed_on' => ['type' => 'string'],
                'time' => ['type' => 'string'],
                'name' => ['type' => 'string'],
                'duration_minutes' => ['type' => 'integer'],
                'intensity' => ['type' => 'string', 'enum' => ['low', 'moderate', 'high']],
                'energy_kcal' => ['type' => 'number'],
                'note' => ['type' => ['string', 'null']],
            ], ['performed_on', 'time', 'name', 'duration_minutes', 'intensity', 'energy_kcal', 'note']),
            $this->tool('preview_weight', 'Propose a body-weight measurement the user must confirm.', [
                'recorded_on' => ['type' => 'string'],
                'weight_kg' => ['type' => 'number'],
                'note' => ['type' => ['string', 'null']],
            ], ['recorded_on', 'weight_kg', 'note']),
            $this->tool('get_report', 'Read calorie intake, activity, and weight for one local date or a date range.', [
                'date' => ['type' => ['string', 'null']],
                'from' => ['type' => ['string', 'null']],
                'to' => ['type' => ['string', 'null']],
            ], ['date', 'from', 'to']),
        ];
    }

    /**
     * @param  array<string, mixed>  $properties
     * @param  list<string>  $required
     * @return array<string, mixed>
     */
    private function tool(string $name, string $description, array $properties, array $required): array
    {
        return [
            'type' => 'function',
            'name' => $name,
            'description' => $description,
            'strict' => true,
            'parameters' => [
                'type' => 'object',
                'additionalProperties' => false,
                'properties' => $properties,
                'required' => $required,
            ],
        ];
    }
}
