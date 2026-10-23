<?php

namespace App\Http\Requests;

use App\Models\BroilerEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBroilerCostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if (! $this->route('entry')) {
            return ['chick_cost' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999']];
        }
        $entry = $this->route('cycle')->entries()->findOrFail($this->route('entry'));
        abort_unless(in_array($entry->kind, ['expense', 'medicine', 'feed_delivery'], true), 404);
        if ($entry->kind === 'feed_delivery') {
            return ['price_per_ton' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:100000000'], 'shipping_cost' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999'], 'payment_status' => ['required', Rule::in(array_keys(BroilerEntry::PAYMENT_STATUSES))]];
        }
        $rules = ['amount' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999'], 'description' => ['required', 'string', 'max:255']];
        if ($entry->kind === 'expense') {
            $rules['category'] = ['required', Rule::in(array_keys(BroilerEntry::EXPENSE_CATEGORIES))];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return ['chick_cost' => 'ثمن الكتاكيت', 'amount' => 'المبلغ', 'description' => 'البيان', 'category' => 'تصنيف المصروف', 'price_per_ton' => 'سعر الطن', 'shipping_cost' => 'النقل', 'payment_status' => 'طريقة الدفع'];
    }
}
