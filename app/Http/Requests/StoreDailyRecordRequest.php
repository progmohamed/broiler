<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDailyRecordRequest extends FormRequest
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
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'recorded_on' => ['required', 'date_format:Y-m-d'],
            'production' => ['required', 'integer', 'min:0'],
            'feed_sacks' => ['required', 'numeric', 'min:0', 'multiple_of:0.5'],
            'deaths' => ['required', 'integer', 'min:0'],
            'medicines' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'recorded_on.required' => 'اختر تاريخ السجل.',
            'recorded_on.date_format' => 'اختر تاريخًا صحيحًا للسجل.',
            'production.required' => 'أدخل كمية إنتاج البيض.',
            'production.integer' => 'يجب أن تكون كمية إنتاج البيض عددًا صحيحًا.',
            'production.min' => 'لا يمكن أن تكون كمية إنتاج البيض سالبة.',
            'feed_sacks.required' => 'أدخل عدد شكاير العلف.',
            'feed_sacks.numeric' => 'يجب أن يكون عدد شكاير العلف رقمًا.',
            'feed_sacks.min' => 'لا يمكن أن يكون عدد شكاير العلف سالبًا.',
            'feed_sacks.multiple_of' => 'يمكن إدخال عدد كامل أو نصف شيكارة فقط.',
            'deaths.required' => 'أدخل عدد النافق.',
            'deaths.integer' => 'يجب أن يكون عدد النافق عددًا صحيحًا.',
            'deaths.min' => 'لا يمكن أن يكون عدد النافق سالبًا.',
            'medicines.max' => 'تفاصيل الأدوية أكبر من الحد المسموح.',
            'notes.max' => 'الملاحظات أكبر من الحد المسموح.',
        ];
    }
}
