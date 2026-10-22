<?php

namespace App\Http\Requests;

use App\SalePaymentStatus;
use App\SaleProductType;
use App\SaleUnit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveOtherSaleRequest extends FormRequest
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
            'sold_on' => ['required', 'date_format:Y-m-d'],
            'product_type' => ['required', Rule::enum(SaleProductType::class)],
            'custom_product' => ['nullable', 'string', 'max:100', 'required_if:product_type,'.SaleProductType::Other->value],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999999.999'],
            'unit' => ['required', Rule::enum(SaleUnit::class)],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'buyer' => ['required', 'string', 'max:150'],
            'payment_status' => ['required', Rule::enum(SalePaymentStatus::class)],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sold_on.required' => 'تاريخ البيع مطلوب.',
            'sold_on.date_format' => 'تاريخ البيع غير صحيح.',
            'product_type.required' => 'نوع المبيع مطلوب.',
            'product_type.enum' => 'نوع المبيع غير صحيح.',
            'custom_product.required_if' => 'اكتب اسم المبيع الآخر.',
            'custom_product.max' => 'اسم المبيع يجب ألا يتجاوز 100 حرف.',
            'quantity.required' => 'الكمية مطلوبة.',
            'quantity.numeric' => 'الكمية يجب أن تكون رقمًا.',
            'quantity.gt' => 'يجب أن تكون الكمية أكبر من صفر.',
            'unit.required' => 'وحدة القياس مطلوبة.',
            'unit.enum' => 'وحدة القياس غير صحيحة.',
            'unit_price.required' => 'سعر الوحدة مطلوب.',
            'unit_price.numeric' => 'سعر الوحدة يجب أن يكون رقمًا.',
            'unit_price.min' => 'سعر الوحدة لا يمكن أن يكون سالبًا.',
            'buyer.required' => 'اسم المشتري مطلوب.',
            'buyer.max' => 'اسم المشتري يجب ألا يتجاوز 150 حرفًا.',
            'payment_status.required' => 'حالة الدفع مطلوبة.',
            'payment_status.enum' => 'حالة الدفع غير صحيحة.',
            'invoice_number.max' => 'رقم الفاتورة يجب ألا يتجاوز 100 حرف.',
            'notes.max' => 'الملاحظات يجب ألا تتجاوز 1000 حرف.',
        ];
    }
}
