<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RestoreFarmBackupRequest extends FormRequest
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
            'backup' => ['required', 'file', 'max:10240'],
            'confirmation' => ['required', 'in:استعادة'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'backup.required' => 'اختر ملف النسخة الاحتياطية.',
            'backup.file' => 'ملف النسخة الاحتياطية غير صالح.',
            'backup.max' => 'حجم النسخة الاحتياطية يجب ألا يتجاوز 10 ميجابايت.',
            'confirmation.required' => 'اكتب كلمة استعادة للتأكيد.',
            'confirmation.in' => 'اكتب كلمة استعادة بشكل صحيح للتأكيد.',
        ];
    }
}
