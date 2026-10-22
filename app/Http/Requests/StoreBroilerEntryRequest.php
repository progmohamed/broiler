<?php

namespace App\Http\Requests;

use App\Models\BroilerEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBroilerEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $kind = $this->input('kind');
        $rules = [
            'kind' => ['required', Rule::in(array_keys(BroilerEntry::TYPES))],
            'recorded_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
        if (in_array($kind, ['daily', 'weight', 'sale'], true)) {
            $rules['birds'] = ['required', 'integer', $kind === 'daily' ? 'min:0' : 'min:1', 'max:1000000'];
        }
        if ($kind === 'daily') {
            $rules['feed_sacks'] = ['required', 'numeric', 'decimal:0,3', 'min:0', 'max:20000'];
        }
        if ($kind === 'feed_delivery') {
            $rules['weight_tons'] = ['required', 'numeric', 'decimal:0,3', 'min:0.001', 'max:1000'];
            $rules['price_per_ton'] = ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:100000000'];
        }
        if (in_array($kind, ['weight', 'sale'], true)) {
            $rules['weight_kg'] = ['required', 'numeric', 'min:0.001', 'max:10000000'];
        }
        if ($kind === 'sale') {
            $rules['unit_price'] = ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:100000'];
        }
        if (in_array($kind, ['medicine', 'expense'], true)) {
            $rules['amount'] = ['required', 'numeric', 'min:0', 'max:999999999'];
            $rules['description'] = ['required', 'string', 'max:255'];
        }
        if ($kind === 'daily') {
            $rules['medicines'] = ['nullable', 'string', 'max:1000'];
        }
        if ($kind === 'expense') {
            $rules['category'] = ['required', Rule::in(array_keys(BroilerEntry::EXPENSE_CATEGORIES))];
        }
        if ($kind === 'feed_delivery') {
            $rules['feed_type'] = ['required', 'string', 'max:100'];
            $rules['supplier'] = ['nullable', 'string', 'max:150'];
            $rules['invoice_number'] = ['nullable', 'string', 'max:100'];
            $rules['shipping_cost'] = ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999'];
        }
        if ($kind === 'sale') {
            $rules['paid'] = ['required', 'numeric', 'min:0', 'max:999999999'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return ['feed_sacks' => 'عدد شكاير العلف', 'weight_tons' => 'كمية العلف بالطن', 'price_per_ton' => 'سعر الطن', 'medicines' => 'الدواء', 'recorded_on' => 'التاريخ', 'birds' => 'عدد الطيور', 'feed_kg' => 'العلف', 'weight_kg' => 'الوزن', 'unit_price' => 'سعر الكيلو', 'amount' => 'التكلفة', 'paid' => 'المبلغ المحصل', 'description' => 'البيان', 'category' => 'تصنيف المصروف', 'feed_type' => 'نوع العلف', 'shipping_cost' => 'تكلفة النقل', 'supplier' => 'المورد'];
    }
}
