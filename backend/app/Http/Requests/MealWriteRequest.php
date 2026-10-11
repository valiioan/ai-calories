<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MealWriteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $items = $this->input('items');
        if (! is_array($items)) {
            return;
        }

        $this->merge([
            'items' => array_map(function ($item) {
                if (! is_array($item)) {
                    return $item;
                }
                if (($item['unit'] ?? null) === 'piece') {
                    $item['unit'] = 'serving';
                }

                return $item;
            }, $items),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'eaten_on' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],
            'meal_type' => ['required', Rule::in(['breakfast', 'lunch', 'dinner', 'snack'])],
            'note' => ['nullable', 'string', 'max:255'],
            'original_text' => ['nullable', 'string', 'max:4000'],
            'assumptions' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.food_id' => ['nullable', 'integer'],
            'items.*.custom_name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0', 'max:10000'],
            'items.*.unit' => ['required', Rule::in(['g', 'ml', 'serving'])],
            'items.*.energy_kcal' => ['nullable', 'numeric', 'between:0,5000'],
            'items.*.protein_g' => ['nullable', 'numeric', 'between:0,1000'],
            'items.*.carbohydrate_g' => ['nullable', 'numeric', 'between:0,1000'],
            'items.*.fat_g' => ['nullable', 'numeric', 'between:0,1000'],
            'items.*.fiber_g' => ['nullable', 'numeric', 'between:0,1000'],
        ];
    }
}
