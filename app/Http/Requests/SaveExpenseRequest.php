<?php

namespace App\Http\Requests;

use App\ExpenseCategory;
use App\ExpensePaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveExpenseRequest extends FormRequest
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
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'spent_on' => ['required', 'date_format:Y-m-d'],
            'category' => ['required', Rule::enum(ExpenseCategory::class), Rule::notIn([
                ExpenseCategory::Feed->value,
                ExpenseCategory::WorkerAdvance->value,
            ])],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'payment_method' => ['required', Rule::enum(ExpensePaymentMethod::class)],
            'payee' => ['nullable', 'string', 'max:150'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'spent_on.required' => 'تاريخ المصروف مطلوب.',
            'spent_on.date_format' => 'تاريخ المصروف غير صحيح.',
            'category.required' => 'تصنيف المصروف مطلوب.',
            'category.enum' => 'تصنيف المصروف غير صحيح.',
            'category.not_in' => 'هذا المصروف يُسجل تلقائيًا من صفحته المخصصة.',
            'amount.required' => 'مبلغ المصروف مطلوب.',
            'amount.numeric' => 'يجب إدخال مبلغ صحيح.',
            'amount.gt' => 'يجب أن يكون مبلغ المصروف أكبر من صفر.',
            'amount.max' => 'مبلغ المصروف أكبر من الحد المسموح.',
            'payment_method.required' => 'طريقة الدفع مطلوبة.',
            'payment_method.enum' => 'طريقة الدفع غير صحيحة.',
            '*.max' => 'القيمة المدخلة أكبر من الحد المسموح.',
        ];
    }
}
