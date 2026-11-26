<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminHealthActionRequest extends FormRequest
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
        return ['source' => ['required', Rule::in(['failed_job', 'webhook', 'ai_provider', 'exam'])], 'source_id' => ['required', 'string', 'max:191'], 'action' => ['required', Rule::in(['acknowledge', 'resolve', 'retry'])], 'reason' => ['required', 'string', 'min:3', 'max:500'], 'idempotency_key' => ['required', 'string', 'max:191']];
    }
}
