<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ExamStreamAnswerRequest extends FormRequest
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
            // Either the recorded voice (transcribed locally) or text from the browser.
            'audio' => ['nullable', 'file', 'max:20480'],
            'answer' => ['nullable', 'string', 'max:5000', 'required_without_all:audio,time_up'],
            // Coaching: the timer ran out while the person was silent; let the coach close.
            'time_up' => ['nullable', 'boolean'],
            'duration_seconds' => ['required', 'integer', 'min:0', 'max:900'],
            'timestamp_seconds' => ['required', 'integer', 'min:0'],
            'facial' => ['nullable', 'array'],
            'facial.eye_contact' => ['nullable', 'numeric', 'between:0,100'],
            'facial.face_visible' => ['nullable', 'numeric', 'between:0,100'],
            'facial.smile' => ['nullable', 'numeric', 'between:0,100'],
            'facial.tension' => ['nullable', 'numeric', 'between:0,100'],
            'facial.samples' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
