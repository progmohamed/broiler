<?php

namespace App\Http\Controllers;

use App\BreakEvenReport;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BreakEvenController extends Controller
{
    public function index(Request $request, BreakEvenReport $breakEvenReport): View
    {
        $validated = $request->validate([
            'current_debt' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'egg_inventory_cartons' => ['nullable', 'numeric', 'min:0', 'max:999999999.9'],
            'average_sale_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'daily_production_cartons' => ['nullable', 'numeric', 'min:0', 'max:999999999.9'],
            'feed_stock_days' => ['nullable', 'numeric', 'min:0', 'max:9999.9'],
            'daily_feed_kilograms' => ['nullable', 'numeric', 'min:0', 'max:999999999.9'],
            'feed_delivery_tons' => ['nullable', 'numeric', 'min:0', 'max:999999.999'],
            'feed_delivery_cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'daily_expenses' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'collection_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ], [
            '*.numeric' => 'أدخل قيمة رقمية صحيحة.',
            '*.min' => 'لا يمكن أن تكون القيمة سالبة.',
            'collection_percentage.max' => 'نسبة التحصيل لا يمكن أن تتجاوز 100%.',
        ]);

        $defaults = $breakEvenReport->defaults(today());
        $inputs = collect($defaults)
            ->mapWithKeys(fn (float $default, string $key): array => [
                $key => isset($validated[$key]) ? (float) $validated[$key] : $default,
            ])
            ->all();

        return view('break-even.index', [
            'report' => $breakEvenReport->build(today(), $inputs),
            'inputs' => $inputs,
        ]);
    }
}
