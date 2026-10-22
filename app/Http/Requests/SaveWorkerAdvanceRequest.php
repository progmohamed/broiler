<?php

namespace App\Http\Requests;

use App\ExpensePaymentMethod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveWorkerAdvanceRequest extends FormRequest
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
            'advanced_on' => ['required', 'date_format:Y-m-d'],
            'worker_name' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'payment_method' => ['required', Rule::enum(ExpensePaymentMethod::class)],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'advanced_on.required' => 'تاريخ السلفة مطلوب.',
            'advanced_on.date_format' => 'تاريخ السلفة غير صحيح.',
            'worker_name.required' => 'اسم العامل مطلوب.',
            'worker_name.max' => 'اسم العامل يجب ألا يتجاوز 150 حرفًا.',
            'amount.required' => 'مبلغ السلفة مطلوب.',
            'amount.numeric' => 'مبلغ السلفة يجب أن يكون رقمًا.',
            'amount.gt' => 'يجب أن يكون مبلغ السلفة أكبر من صفر.',
            'payment_method.required' => 'طريقة الدفع مطلوبة.',
            'payment_method.enum' => 'طريقة الدفع غير صحيحة.',
            'notes.max' => 'الملاحظات يجب ألا تتجاوز 1000 حرف.',
        ];
    }
}
