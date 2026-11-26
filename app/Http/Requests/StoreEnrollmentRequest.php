<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole('teacher', 'admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'period_number' => ['required', 'integer', 'min:1'],
            'status' => ['sometimes', 'string', 'in:active,suspended'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'source' => ['nullable', 'string', 'max:50'],
        ];
    }
}
