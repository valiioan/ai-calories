<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $nullable = [
            'birth_date',
            'sex',
            'height_cm',
            'activity_level',
            'calorie_target',
            'protein_target_g',
            'carbohydrate_target_g',
            'fat_target_g',
        ];
        $merged = [];

        foreach ($nullable as $field) {
            if ($this->exists($field) && $this->input($field) === '') {
                $merged[$field] = null;
            }
        }

        if ($merged !== []) {
            $this->merge($merged);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'birth_date' => ['sometimes', 'nullable', 'date', 'after:1900-01-01', 'before_or_equal:today'],
            'sex' => ['sometimes', 'nullable', Rule::in(['female', 'male', 'other', 'unspecified'])],
            'height_cm' => ['sometimes', 'nullable', 'numeric', 'between:100,250'],
            'activity_level' => ['sometimes', 'nullable', Rule::in(['sedentary', 'light', 'moderate', 'active', 'very_active'])],
            'goal' => ['sometimes', 'required', Rule::in(['lose', 'maintain', 'gain'])],
            'calorie_target' => ['sometimes', 'nullable', 'integer', 'between:800,6000'],
            'protein_target_g' => ['sometimes', 'nullable', 'numeric', 'between:0,2000'],
            'carbohydrate_target_g' => ['sometimes', 'nullable', 'numeric', 'between:0,2000'],
            'fat_target_g' => ['sometimes', 'nullable', 'numeric', 'between:0,2000'],
            'timezone' => ['sometimes', 'required', 'timezone'],
        ];
    }
}
