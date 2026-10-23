<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBroilerHouseExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['description' => ['required', 'string', 'max:255'], 'amount' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999999999'], 'starts_on' => ['required', 'date_format:Y-m-d'], 'ends_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:starts_on']];
    }

    public function attributes(): array
    {
        return ['description' => 'المصروف', 'amount' => 'المبلغ', 'starts_on' => 'بداية المدة', 'ends_on' => 'نهاية المدة'];
    }
}
