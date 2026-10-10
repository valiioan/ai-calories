<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'locale' => ['sometimes', 'required', Rule::in(['ro', 'en'])],
            'appearance' => ['sometimes', 'required', Rule::in(['light', 'dark', 'system'])],
            'units' => ['sometimes', 'required', Rule::in(['metric', 'imperial'])],
            'notify_meals' => ['sometimes', 'required', 'boolean'],
            'notify_weight' => ['sometimes', 'required', 'boolean'],
            'notify_weekly' => ['sometimes', 'required', 'boolean'],
        ];
    }
}
