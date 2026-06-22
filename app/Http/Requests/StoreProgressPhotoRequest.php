<?php

declare(strict_types=1);

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
        $mimetypes = implode(',', config('progress_photos.accepted_mimetypes'));
        $maxKb     = config('progress_photos.max_size_kb');

        return [
            'photo'    => ['required', 'file', "mimetypes:{$mimetypes}", "max:{$maxKb}"],
            'taken_at' => ['required', 'date'],
            'caption'  => ['nullable', 'string', 'max:200'],
        ];
    }
}
