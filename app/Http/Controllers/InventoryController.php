<?php

namespace App\Http\Controllers;

use App\Models\DailyRecord;
use App\Models\EggSale;
use App\Models\FeedDelivery;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'as_of' => ['nullable', 'date_format:Y-m-d'],
        ], [
            'as_of.date_format' => 'تاريخ عرض المخزون غير صحيح.',
        ]);

        $asOf = isset($filters['as_of'])
            ? Carbon::createFromFormat('Y-m-d', $filters['as_of'])
            : today();

        $feedDeliveryQuery = FeedDelivery::query()->whereDate('delivered_on', '<=', $asOf);
        $dailyRecordQuery = DailyRecord::query()->whereDate('recorded_on', '<=', $asOf);
        $eggSaleQuery = EggSale::query()->whereDate('sold_on', '<=', $asOf);

        $deliveredFeedKilograms = (float) (clone $feedDeliveryQuery)->sum('weight_tons') * FeedDelivery::KILOGRAMS_PER_TON;
        $usedFeedKilograms = (float) (clone $dailyRecordQuery)->sum('feed');
        $remainingFeedKilograms = $deliveredFeedKilograms - $usedFeedKilograms;
        $recentFeedUsage = (clone $dailyRecordQuery)
            ->where('feed', '>', 0)
            ->latest('recorded_on')
            ->limit(7)
            ->pluck('feed');
        $averageDailyFeedKilograms = $recentFeedUsage->isEmpty() ? 0.0 : (float) $recentFeedUsage->average();
        $feedDaysRemaining = $remainingFeedKilograms > 0 && $averageDailyFeedKilograms > 0
            ? $remainingFeedKilograms / $averageDailyFeedKilograms
            : null;

        $producedEggs = (int) (clone $dailyRecordQuery)->sum('production');
        $soldEggs = (int) (clone $eggSaleQuery)->sum('cartons') * EggSale::EGGS_PER_CARTON;
        $remainingEggs = $producedEggs - $soldEggs;

        $feedMovements = (clone $feedDeliveryQuery)
            ->latest('delivered_on')
            ->latest('id')
            ->limit(8)
            ->get(['id', 'delivered_on', 'feed_type', 'supplier', 'weight_tons'])
            ->map(fn (FeedDelivery $delivery): array => [
                'id' => $delivery->id,
                'occurred_on' => $delivery->delivered_on,
                'direction' => 'in',
                'title' => 'توريد '.$delivery->feed_type,
                'description' => $delivery->supplier,
                'amount' => $delivery->weightInKilograms(),
            ])
            ->merge(
                (clone $dailyRecordQuery)
                    ->where('feed', '>', 0)
                    ->latest('recorded_on')
                    ->latest('id')
                    ->limit(8)
                    ->get(['id', 'recorded_on', 'feed'])
                    ->map(fn (DailyRecord $record): array => [
                        'id' => $record->id,
                        'occurred_on' => $record->recorded_on,
                        'direction' => 'out',
                        'title' => 'استهلاك يومي',
                        'description' => number_format($record->feed / FeedDelivery::SACK_WEIGHT_KILOGRAMS, 1).' شيكارة',
                        'amount' => (float) $record->feed,
                    ])
            )
            ->sortByDesc(fn (array $movement): string => $movement['occurred_on']->format('Y-m-d').'-'.str_pad((string) $movement['id'], 10, '0', STR_PAD_LEFT))
            ->take(10)
            ->values();

        $eggMovements = (clone $dailyRecordQuery)
            ->where('production', '>', 0)
            ->latest('recorded_on')
            ->latest('id')
            ->limit(8)
            ->get(['id', 'recorded_on', 'production'])
            ->map(fn (DailyRecord $record): array => [
                'id' => $record->id,
                'occurred_on' => $record->recorded_on,
                'direction' => 'in',
                'title' => 'إنتاج يومي',
                'description' => number_format($record->production).' بيضة',
                'amount' => (int) $record->production,
            ])
            ->merge(
                (clone $eggSaleQuery)
                    ->latest('sold_on')
                    ->latest('id')
                    ->limit(8)
                    ->get(['id', 'sold_on', 'buyer', 'cartons'])
                    ->map(fn (EggSale $sale): array => [
                        'id' => $sale->id,
                        'occurred_on' => $sale->sold_on,
                        'direction' => 'out',
                        'title' => 'بيع بيض',
                        'description' => $sale->buyer.' · '.number_format($sale->cartons).' كرتونة',
                        'amount' => $sale->cartons * EggSale::EGGS_PER_CARTON,
                    ])
            )
            ->sortByDesc(fn (array $movement): string => $movement['occurred_on']->format('Y-m-d').'-'.str_pad((string) $movement['id'], 10, '0', STR_PAD_LEFT))
            ->take(10)
            ->values();

        return view('inventory.index', [
            'asOf' => $asOf,
            'deliveredFeedKilograms' => $deliveredFeedKilograms,
            'usedFeedKilograms' => $usedFeedKilograms,
            'remainingFeedKilograms' => $remainingFeedKilograms,
            'remainingFeedSacks' => $remainingFeedKilograms / FeedDelivery::SACK_WEIGHT_KILOGRAMS,
            'averageDailyFeedKilograms' => $averageDailyFeedKilograms,
            'feedDaysRemaining' => $feedDaysRemaining,
            'producedEggs' => $producedEggs,
            'soldEggs' => $soldEggs,
            'remainingEggs' => $remainingEggs,
            'remainingEggCartons' => $remainingEggs / EggSale::EGGS_PER_CARTON,
            'feedMovements' => $feedMovements,
            'eggMovements' => $eggMovements,
        ]);
    }
}
