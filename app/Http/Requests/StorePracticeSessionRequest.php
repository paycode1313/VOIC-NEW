<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePracticeSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'scenario_type' => ['required', 'string', 'max:100'],
            'ai_role_id' => ['nullable', 'exists:ai_roles,id'],
            'duration_seconds' => ['required', 'integer', 'min:1'],
            'face_score' => ['nullable', 'numeric', 'between:0,100'],
            'voice_score' => ['nullable', 'numeric', 'between:0,100'],
            'overall_score' => ['required', 'numeric', 'between:0,100'],
            'ai_conclusion' => ['nullable', 'string'],
            'feedback_notes' => ['nullable', 'array'],
            'feedback_notes.summary' => ['nullable', 'string', 'max:1000'],
            'feedback_notes.eye_contact_score' => ['nullable', 'numeric', 'between:0,100'],
            'feedback_notes.smile_rate' => ['nullable', 'numeric', 'between:0,100'],
            'feedback_notes.pace_wpm' => ['nullable', 'numeric', 'min:0'],
            'feedback_notes.clarity_score' => ['nullable', 'numeric', 'between:0,100'],
        ];
    }
}
