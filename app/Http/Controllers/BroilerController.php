<?php

namespace App\Http\Controllers;

use App\BroilerLedger;
use App\Http\Requests\StoreBroilerCycleRequest;
use App\Http\Requests\StoreBroilerEntryRequest;
use App\Http\Requests\UpdateBroilerCostRequest;
use App\Http\Requests\UpdateBroilerDailyRequest;
use App\Models\BroilerCycle;
use App\Models\BroilerEntry;
use App\Models\BroilerHouse;
use App\Models\FarmSetting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class BroilerController extends Controller
{
    public function index(): View
    {
        return view('broiler.index', ['houses' => BroilerHouse::query()->orderBy('name')->get(), 'cycles' => BroilerCycle::query()->with('house')->latest('id')->paginate(20)]);
    }

    public function updatePin(Request $request): RedirectResponse
    {
        $data = $request->validate(['pin' => ['required', 'digits_between:4,8', 'confirmed']]);
        $setting = FarmSetting::query()->firstOrCreate([], ['farm_name' => config('app.name'), 'herd_birth_date' => today(), 'total_herd_count' => 0]);
        $setting->app_pin = Hash::make($data['pin']);
        $setting->save();
        $request->session()->regenerate();
        $request->session()->put('farm_unlocked', true);

        return to_route('broiler.index')->with('status', 'تم حفظ رمز القفل.');
    }

    public function storeHouse(Request $request): RedirectResponse
    {
        BroilerHouse::query()->create($request->validate(['name' => ['required', 'string', 'max:150', 'unique:broiler_houses,name'], 'capacity' => ['required', 'integer', 'min:1', 'max:1000000']]));

        return to_route('broiler.index')->with('status', 'تم إضافة العنبر.');
    }

    public function store(StoreBroilerCycleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $cycle = DB::transaction(function () use ($data): BroilerCycle {
            $house = BroilerHouse::query()->lockForUpdate()->findOrFail($data['broiler_house_id']);
            if ($house->cycles()->whereNull('closed_on')->exists()) {
                throw ValidationException::withMessages(['broiler_house_id' => 'توجد دورة مفتوحة في هذا العنبر.']);
            }
            if ($data['initial_birds'] > $house->capacity) {
                throw ValidationException::withMessages(['initial_birds' => 'عدد الكتاكيت يتجاوز سعة العنبر.']);
            }
            if ($house->cycles()->where('closed_on', '>=', $data['started_on'])->exists()) {
                throw ValidationException::withMessages(['started_on' => 'بداية الدورة يجب أن تكون بعد نهاية الدورة السابقة.']);
            }

            return BroilerCycle::query()->create($data);
        });

        return to_route('broiler.show', $cycle)->with('status', 'تم بدء الدورة.');
    }

    public function show(Request $request, BroilerCycle $cycle, BroilerLedger $ledger): View
    {
        $tabs = ['daily' => 'اليوميات', 'feed' => 'العلف', 'costs' => 'المصروفات', 'sales' => 'المبيعات', 'reports' => 'التقارير', 'vaccinations' => 'التحصينات'];
        $category = $request->query('category', '');
        if (! is_string($category) || ! in_array($category, ['', 'feed', ...array_keys(BroilerEntry::EXPENSE_CATEGORIES)], true)) {
            $category = '';
        }
        $activeTab = $request->session()->get('cycle_tab', $request->query('tab', 'daily'));
        if (! is_string($activeTab) || ! array_key_exists($activeTab, $tabs)) {
            $activeTab = 'daily';
        }
        $old = $request->session()->getOldInput();
        if (isset($old['daily_entry'])) {
            $activeTab = 'daily';
        } elseif (isset($old['vaccination_form'])) {
            $activeTab = 'vaccinations';
        } elseif (isset($old['cost_entry']) || isset($old['cost_form'])) {
            $activeTab = isset($old['cost_entry']) && $cycle->entries()->whereKey($old['cost_entry'])->where('kind', 'feed_delivery')->exists() ? 'feed' : 'costs';
        } elseif (isset($old['kind'])) {
            $activeTab = match ($old['kind']) {
                'sale' => 'sales', 'feed_delivery' => 'feed', 'expense', 'medicine' => 'costs', default => 'daily'
            };
        }
        $costQuery = $cycle->entries()->whereIn('kind', $category === 'feed' ? ['feed_delivery'] : ['expense', 'medicine']);
        if ($category === 'feed') {
            $costQuery->where('kind', 'feed_delivery');
        } elseif ($category !== '') {
            $costQuery->where(function ($query) use ($category): void {
                $query->where('category', $category);
                if ($category === 'medicines') {
                    $query->orWhere('kind', 'medicine');
                }
                if ($category === 'other') {
                    $query->orWhere(fn ($legacy) => $legacy->where('kind', 'expense')->whereNull('category'));
                }
            });
        }

        return view('broiler.show', [
            'tabs' => $tabs, 'activeTab' => $activeTab, 'category' => $category,
            'vaccinations' => $cycle->vaccinations()->orderBy('due_at')->orderBy('id')->get(),
            'cycle' => $cycle->load('house'), 'summary' => $ledger->summary($cycle),
            'dailyEntries' => $cycle->entries()->whereIn('kind', ['daily', 'weight'])->orderByDesc('recorded_on')->orderByDesc('id')->paginate(15, ['*'], 'daily_page')->appends(['tab' => 'daily']),
            'costEntries' => $costQuery->orderByDesc('recorded_on')->orderByDesc('id')->paginate(20, ['*'], 'cost_page')->appends(['tab' => 'costs', 'category' => $category]),
            'feedEntries' => $cycle->entries()->where('kind', 'feed_delivery')->orderByDesc('recorded_on')->orderByDesc('id')->paginate(20, ['*'], 'feed_page')->appends(['tab' => 'feed']),
            'saleEntries' => $cycle->entries()->where('kind', 'sale')->orderByDesc('recorded_on')->orderByDesc('id')->paginate(20, ['*'], 'sale_page')->appends(['tab' => 'sales']),
            'entries' => $cycle->entries()->orderByDesc('recorded_on')->orderByDesc('id')->paginate(30)->appends(['tab' => 'reports']),
        ]);
    }

    public function record(StoreBroilerEntryRequest $request, BroilerCycle $cycle, BroilerLedger $ledger): RedirectResponse
    {
        $ledger->record($cycle, $request->validated());

        return to_route('broiler.show', $cycle)->with('cycle_tab', match ($request->validated('kind')) {
            'sale' => 'sales', 'feed_delivery' => 'feed', 'expense', 'medicine' => 'costs', default => 'daily'
        })->with('status', 'تم حفظ السجل.');
    }

    public function updateChickCost(UpdateBroilerCostRequest $request, BroilerCycle $cycle): RedirectResponse
    {
        DB::transaction(function () use ($request, $cycle): void {
            BroilerCycle::query()->lockForUpdate()->findOrFail($cycle->id)->update($request->validated());
        });

        return to_route('broiler.show', $cycle)->with('cycle_tab', 'costs')->with('status', 'تم تعديل ثمن الكتاكيت.');
    }

    public function updateCost(UpdateBroilerCostRequest $request, BroilerCycle $cycle, int $entry): RedirectResponse
    {
        DB::transaction(function () use ($request, $cycle, $entry): void {
            $cycle = BroilerCycle::query()->lockForUpdate()->findOrFail($cycle->id);
            $record = $cycle->entries()->lockForUpdate()->findOrFail($entry);
            abort_if($record->medicine_transfer_id !== null, 422, 'هذه التكلفة مرتبطة بالمخزن. استخدم المرتجع من شاشة صرف الدواء.');
            abort_unless(in_array($record->kind, ['expense', 'medicine', 'feed_delivery'], true), 404);
            $data = $request->validated();
            if ($record->kind === 'feed_delivery') {
                $data['amount'] = round($record->feed_kg / 1000 * $data['price_per_ton'] + $data['shipping_cost'], 2);
                $data['unit_price'] = round($data['price_per_ton'] / 1000, 2);
            }
            $record->update($data);
        });

        return to_route('broiler.show', $cycle)->with('cycle_tab', $cycle->entries()->whereKey($entry)->value('kind') === 'feed_delivery' ? 'feed' : 'costs')->with('status', 'تم تعديل المصروف وتحديث الحسابات.');
    }

    public function updateDaily(UpdateBroilerDailyRequest $request, BroilerCycle $cycle, int $entry, BroilerLedger $ledger): RedirectResponse
    {
        $ledger->updateDaily($cycle, $entry, $request->validated());

        return to_route('broiler.show', [$cycle, 'tab' => 'daily'])->with('cycle_tab', 'daily')->with('status', 'تم تعديل اليومية وتحديث الحسابات.');
    }

    public function destroyCost(BroilerCycle $cycle, int $entry): RedirectResponse
    {
        DB::transaction(function () use ($cycle, $entry): void {
            $cycle = BroilerCycle::query()->lockForUpdate()->findOrFail($cycle->id);
            $record = $cycle->entries()->lockForUpdate()->findOrFail($entry);
            abort_if($record->medicine_transfer_id !== null, 422, 'هذه التكلفة مرتبطة بالمخزن. استخدم المرتجع من شاشة صرف الدواء.');
            abort_unless(in_array($record->kind, ['expense', 'medicine'], true), 404);
            $record->delete();
        });

        return to_route('broiler.show', [$cycle, 'tab' => 'costs'])->with('cycle_tab', 'costs')->with('status', 'تم حذف المصروف وتحديث الحسابات.');
    }

    public function close(BroilerCycle $cycle, BroilerLedger $ledger): RedirectResponse
    {
        $ledger->close($cycle);

        return to_route('broiler.show', $cycle)->with('status', 'تم إغلاق الدورة.');
    }
}
