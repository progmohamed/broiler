<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFarmSettingRequest extends FormRequest
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
            'farm_name' => ['nullable', 'string', 'max:100'],
            'herd_birth_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'total_herd_count' => ['required', 'integer', 'min:1'],
            'new_pin' => ['nullable', 'digits_between:4,8', 'confirmed'],
            'new_pin_confirmation' => ['nullable'],
            'remove_pin' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'farm_name.string' => 'يجب أن يكون اسم المزرعة نصًا.',
            'farm_name.max' => 'يجب ألا يتجاوز اسم المزرعة 100 حرف.',
            'herd_birth_date.required' => 'اختر تاريخ ميلاد القطيع.',
            'herd_birth_date.date_format' => 'اختر تاريخًا صحيحًا لميلاد القطيع.',
            'herd_birth_date.before_or_equal' => 'لا يمكن أن يكون تاريخ ميلاد القطيع في المستقبل.',
            'total_herd_count.required' => 'أدخل إجمالي عدد القطيع.',
            'total_herd_count.integer' => 'يجب أن يكون إجمالي عدد القطيع عددًا صحيحًا.',
            'total_herd_count.min' => 'يجب ألا يقل إجمالي عدد القطيع عن طائر واحد.',
            'new_pin.digits_between' => 'رقم PIN يجب أن يكون من 4 إلى 8 أرقام.',
            'new_pin.confirmed' => 'تأكيد رقم PIN غير مطابق.',
        ];
    }
}
