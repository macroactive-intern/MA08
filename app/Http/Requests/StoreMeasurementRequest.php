<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMeasurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'measured_at'         => ['required', 'date', Rule::unique('measurements')->where('user_id', $this->user()->id)],
            'weight'              => ['nullable', 'numeric'],
            'body_fat_percentage' => ['nullable', 'numeric'],
            'notes'               => ['nullable', 'string'],
            'unit_system'         => ['required', Rule::in(['metric', 'imperial'])],
        ];
    }
}
