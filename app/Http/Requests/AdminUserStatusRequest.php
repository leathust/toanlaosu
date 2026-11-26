<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::in(['active', 'suspended', 'disabled'])], 'reason' => ['required', 'string', 'min:3', 'max:500']];
    }
}
