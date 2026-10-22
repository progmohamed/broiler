<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEggSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'sold_on' => ['required', 'date_format:Y-m-d'],
            'cartons' => ['required', 'integer', 'min:1'],
            'price_per_carton' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'buyer' => ['required', 'string', 'max:150'],
            'payment_status' => ['required', 'in:paid,pending'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'sold_on.required' => 'تاريخ البيع مطلوب.',
            'sold_on.date_format' => 'تاريخ البيع غير صحيح.',
            'cartons.required' => 'عدد الكراتين مطلوب.',
            'cartons.integer' => 'يجب إدخال عدد كراتين صحيح.',
            'cartons.min' => 'يجب أن يكون عدد الكراتين واحدًا على الأقل.',
            'price_per_carton.required' => 'سعر الكرتونة مطلوب.',
            'price_per_carton.numeric' => 'يجب إدخال سعر صحيح.',
            'price_per_carton.min' => 'لا يمكن أن يكون السعر سالبًا.',
            'price_per_carton.max' => 'السعر أكبر من الحد المسموح.',
            'buyer.required' => 'اسم المشتري مطلوب.',
            'payment_status.required' => 'حالة الدفع مطلوبة.',
            'payment_status.in' => 'حالة الدفع غير صحيحة.',
            '*.max' => 'القيمة المدخلة أكبر من الحد المسموح.',
        ];
    }
}
