<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SceneCommandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isActive() ?? false;
    }

    public function rules(): array
    {
        return [
            'command' => ['required', 'string', 'in:play,pause,seek,reset,parameter.update'],
            'value' => ['sometimes'],
        ];
    }
}
