<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionVersionRequest extends FormRequest
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
            'stem' => ['required', 'string', 'max:10000'],
            'solution' => ['nullable', 'string', 'max:30000'],
            'solution_blocks' => ['nullable', 'array', 'max:50'],
            'solution_blocks.*.type' => ['required', 'string', 'in:text,math,image,video,scene'],
            'solution_blocks.*.content' => ['nullable', 'string', 'max:30000'],
            'solution_blocks.*.latex' => ['nullable', 'string', 'max:10000'],
            'solution_blocks.*.media_id' => ['nullable', 'integer', 'exists:lesson_materials,id'],
            'solution_blocks.*.video_id' => ['nullable', 'integer', 'exists:lesson_videos,id'],
            'solution_blocks.*.scene_version_id' => ['nullable', 'integer', 'exists:visual_scene_versions,id'],
        ];
    }
}
