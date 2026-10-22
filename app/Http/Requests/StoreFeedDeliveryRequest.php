<?php

namespace App\Http\Requests;

use App\ExpensePaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFeedDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'delivered_on' => ['required', 'date_format:Y-m-d'],
            'feed_type' => ['required', 'string', 'max:100'],
            'weight_tons' => ['required', 'numeric', 'gt:0', 'max:9999999.999'],
            'price_per_ton' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'shipping_cost' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'tip' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'supplier' => ['required', 'string', 'max:150'],
            'payment_method' => ['required', Rule::enum(ExpensePaymentMethod::class)],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'delivered_on.required' => 'تاريخ التوريد مطلوب.',
            'delivered_on.date_format' => 'تاريخ التوريد غير صحيح.',
            'feed_type.required' => 'نوع العلف مطلوب.',
            'weight_tons.required' => 'وزن العلف مطلوب.',
            'weight_tons.gt' => 'يجب أن يكون وزن العلف أكبر من صفر.',
            'price_per_ton.required' => 'سعر الطن مطلوب.',
            'shipping_cost.required' => 'تكلفة الشحن مطلوبة، ويمكن إدخال صفر.',
            'tip.required' => 'الإكرامية مطلوبة، ويمكن إدخال صفر.',
            'supplier.required' => 'اسم المورد مطلوب.',
            'payment_method.required' => 'طريقة الدفع مطلوبة.',
            'payment_method.enum' => 'طريقة الدفع غير صحيحة.',
            '*.numeric' => 'يجب إدخال رقم صحيح.',
            '*.min' => 'لا يمكن أن تكون القيمة سالبة.',
            '*.max' => 'القيمة المدخلة أكبر من الحد المسموح.',
        ];
    }
}
