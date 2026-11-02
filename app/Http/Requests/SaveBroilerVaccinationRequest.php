<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveBroilerVaccinationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'cycle_day' => ['required_without:scheduled_at', 'integer', 'min:1', 'max:3650'],
            'scheduled_time' => ['required_with:cycle_day', 'date_format:H:i'],
            'scheduled_at' => ['required_without:cycle_day', 'date_format:Y-m-d\TH:i'],
            'remind_before_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return ['cycle_day' => 'يوم الدورة', 'scheduled_time' => 'وقت التحصين', 'name' => 'اسم التحصين', 'scheduled_at' => 'موعد التحصين', 'remind_before_hours' => 'ساعات التنبيه'];
    }
}
