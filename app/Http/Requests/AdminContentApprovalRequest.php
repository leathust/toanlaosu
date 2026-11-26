<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminContentApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:3', 'max:500']];
    }
}
