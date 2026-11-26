<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminAuditExportRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['idempotency_key' => ['required', 'string', 'max:191'], 'range' => ['nullable', Rule::in(['7d', '30d', '90d'])], 'event' => ['nullable', 'string', 'max:120'], 'actor_id' => ['nullable', 'integer', 'min:1'], 'target_type' => ['nullable', 'string', 'max:120'], 'target_id' => ['nullable', 'integer', 'min:1'], 'severity' => ['nullable', Rule::in(['critical', 'warning', 'info'])], 'timezone' => ['nullable', 'timezone'], 'per_page' => ['nullable', 'integer', 'min:10', 'max:100']];
    }
}
