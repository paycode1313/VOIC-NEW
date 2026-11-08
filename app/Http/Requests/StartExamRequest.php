<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StartExamRequest extends FormRequest
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
            'topic' => ['nullable', 'string', 'max:150'],
            'context_material' => ['nullable', 'string', 'max:3000'],
            'language' => ['nullable', 'string', 'in:id,en'],
            'parts' => ['nullable', 'array', 'min:1'],
            'parts.*' => ['integer', 'in:1,2,3'],
            'guest_name' => ['nullable', 'string', 'max:100'],
            'consent' => ['accepted'],
            'focus' => ['nullable', 'string', 'in:fluency,vocabulary,structure,delivery'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'consent.accepted' => 'Centang persetujuan pemrosesan suara terlebih dahulu.',
        ];
    }

    /**
     * Selected exam parts, defaulting to the full three-part mock.
     *
     * @return list<int>
     */
    public function parts(): array
    {
        $parts = array_map('intval', (array) $this->validated('parts', [1, 2, 3]));

        return $parts === [] ? [1, 2, 3] : array_values($parts);
    }
}
