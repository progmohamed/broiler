<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBroilerCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'broiler_house_id' => ['required', 'integer', 'exists:broiler_houses,id'],
            'name' => ['required', 'string', 'max:150'],
            'started_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'initial_birds' => ['required', 'integer', 'min:1', 'max:1000000'],
            'initial_weight_g' => ['required', 'numeric', 'min:1', 'max:1000'],
            'chick_cost' => ['required', 'numeric', 'min:0', 'max:999999999'],
        ];
    }
}
