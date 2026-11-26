<?php

namespace App\Http\Requests;

use App\Models\SystemSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSystemSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        $key = (string) $this->route('key');
        $type = SystemSetting::schema()[$key] ?? null;

        return [
            'value' => ['required', Rule::when($type === 'boolean', ['boolean']), Rule::when($type === 'integer', ['integer', 'min:0'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! array_key_exists((string) $this->route('key'), SystemSetting::schema())) {
            $this->merge(['value' => null]);
        }
    }
}
