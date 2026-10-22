<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AskFarmAssistantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['question' => ['required', 'string', 'max:1000']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'question.required' => 'اكتب سؤالك أولًا.',
            'question.string' => 'يجب أن يكون السؤال نصًا.',
            'question.max' => 'اختصر السؤال إلى 1000 حرف كحد أقصى.',
        ];
    }
}
