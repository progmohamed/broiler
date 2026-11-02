<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBroilerDailyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recorded_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'birds' => ['required', 'integer', 'min:0', 'max:1000000'],
            'feed_sacks' => ['required', 'numeric', 'decimal:0,3', 'min:0', 'max:20000'],
            'medicines' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return ['recorded_on' => 'التاريخ', 'birds' => 'النفوق اليومي', 'feed_sacks' => 'عدد شكاير العلف', 'medicines' => 'الدواء', 'notes' => 'ملاحظات'];
    }
}
