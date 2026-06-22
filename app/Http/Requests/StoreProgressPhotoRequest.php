<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProgressPhotoRequest extends FormRequest
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
            'photo'    => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp', 'max:5120'],
            'taken_at' => ['required', 'date'],
            'caption'  => ['nullable', 'string', 'max:200'],
        ];
    }
}
