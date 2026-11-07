<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ExamAnswerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('practiceSession'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'answer' => ['present', 'nullable', 'string', 'max:5000'],
            'duration_seconds' => ['required', 'integer', 'min:0', 'max:900'],
            'timestamp_seconds' => ['required', 'integer', 'min:0'],
            'facial' => ['nullable', 'array'],
            'facial.eye_contact' => ['nullable', 'numeric', 'between:0,100'],
            'facial.face_visible' => ['nullable', 'numeric', 'between:0,100'],
            'facial.samples' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
