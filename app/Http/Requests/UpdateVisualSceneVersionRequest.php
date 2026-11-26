<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateVisualSceneVersionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'version' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'scene_key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/'],
            'config' => ['present', 'array'],
            'steps' => ['present', 'array'],
            'allowed_commands' => ['required', 'array', 'max:20'],
            'allowed_commands.*' => ['string', 'max:50'],
        ];
    }
}
