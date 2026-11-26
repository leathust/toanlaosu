<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminContentReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return ['decision' => ['required', Rule::in(['approve', 'reject', 'request_changes'])], 'reason' => ['required', 'string', 'min:3', 'max:500'], 'expected_version' => ['nullable', 'integer', 'min:1']];
    }
}
