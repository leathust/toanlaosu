<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminBulkUserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return ['user_ids' => ['required', 'array', 'min:1', 'max:100'], 'user_ids.*' => ['integer', 'distinct', 'exists:users,id'], 'status' => ['required', Rule::in(['active', 'suspended', 'disabled'])], 'reason' => ['required', 'string', 'min:3', 'max:500']];
    }
}
