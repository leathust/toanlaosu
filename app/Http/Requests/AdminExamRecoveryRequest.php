<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminExamRecoveryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['expire'])],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
            'idempotency_key' => ['required', 'string', 'max:191'],
        ];
    }
}
