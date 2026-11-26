<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminAlertStateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['open', 'acknowledged', 'resolved'])],
            'source' => ['required', 'string', 'max:50'],
            'source_id' => ['required', 'string', 'max:191'],
        ];
    }
}
