<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminContentBatchReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return ['type' => ['required', Rule::in(['course'])], 'content_ids' => ['required', 'array', 'min:1', 'max:25'], 'content_ids.*' => ['integer', 'distinct', 'exists:courses,id'], 'decision' => ['required', Rule::in(['approve', 'reject', 'request_changes'])], 'reason' => ['required', 'string', 'min:3', 'max:500'], 'confirm_batch' => ['accepted']];
    }
}
