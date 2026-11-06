<?php

namespace App\Http\Controllers;

use App\MedicineStore;
use App\Models\BroilerCycle;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MedicineTransferController extends Controller
{
    private function destination(Request $request): array
    {
        $cycle = BroilerCycle::query()->with('house')->findOrFail($request->integer('cycle'));

        return [(string) $cycle->id, $cycle->house->name.' / '.$cycle->name, (bool) $cycle->closed_on];
    }

    public function index(Request $request, MedicineStore $store): mixed
    {
        [$destinationId,$destination,$closed] = $this->destination($request);
        $medicines = [];
        $connectionError = null;
        try {
            $medicines = $store->call('GET', 'medicines');
        } catch (\Throwable $e) {
            $connectionError = 'المخزن غير متاح حاليًا. تأكد من إعداد الاتصال وحاول مرة أخرى.';
        }
        $transfers = DB::table('medicine_transfers')->where('destination_id', $destinationId)->orderByDesc('id')->paginate(20)->withQueryString();

        return view('medicine-transfers', compact('destinationId', 'destination', 'closed', 'medicines', 'connectionError', 'transfers'));
    }

    public function quote(Request $request, MedicineStore $store): mixed
    {
        [$destinationId,$destination,$closed] = $this->destination($request);
        abort_if($closed, 422, 'الدورة مغلقة.');
        $data = $request->validate(['medicine_id' => 'required|integer|min:1', 'quantity' => 'required|integer|min:1|max:1000000']);
        try {
            $medicines = collect($store->call('GET', 'medicines'));
            $medicine = $medicines->firstWhere('id', (int) $data['medicine_id']);
            abort_unless($medicine, 422, 'الدواء غير متاح.');
            $quote = $store->call('POST', 'quotes', $data);
        } catch (ValidationException $e) {
            throw $e;
        } catch (HttpClientException $e) {
            return back()->withErrors(['store' => 'تعذر الوصول للمخزن. لم يتم الصرف.'])->withInput();
        }
        DB::table('medicine_transfers')->insert(['request_id' => (string) Str::uuid(), 'medicine_id' => $data['medicine_id'], 'medicine_name' => $medicine['name'], 'quantity' => $data['quantity'], 'destination_id' => $destinationId, 'destination' => $destination, 'expected_total' => $quote['total_cost'], 'status' => 'draft', 'created_at' => now(), 'updated_at' => now()]);

        return redirect()->route('medicine-transfers.index', ['cycle' => $destinationId])->with('status', 'راجع التكلفة واضغط تأكيد الصرف.');
    }

    public function confirm(Request $request, int $transfer, MedicineStore $store): mixed
    {
        [$destinationId,,$closed] = $this->destination($request);
        DB::transaction(function () use ($transfer, $destinationId, $closed): void {
            $t = DB::table('medicine_transfers')->where('id', $transfer)->where('destination_id', $destinationId)->lockForUpdate()->firstOrFail();
            if ($t->status === 'draft') {
                abort_if($closed, 422, 'الدورة مغلقة.');
                DB::table('medicine_transfers')->where('id', $t->id)->update(['status' => 'pending', 'updated_at' => now()]);
            }
        });
        $store->sync($transfer);

        return back()->with('status', 'تمت مراجعة الطلب. حالة التنفيذ موضحة أمامه.');
    }

    public function returns(Request $request, int $transfer, MedicineStore $store): mixed
    {
        [$destinationId] = $this->destination($request);
        DB::transaction(function () use ($transfer, $destinationId): void {
            $t = DB::table('medicine_transfers')->where('id', $transfer)->where('destination_id', $destinationId)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($t->status, ['completed', 'returning', 'returned']), 422);
            if ($t->status === 'completed') {
                DB::table('medicine_transfers')->where('id', $t->id)->update(['status' => 'returning', 'updated_at' => now()]);
            }
        });
        $store->sync($transfer);

        return back()->with('status','تمت مراجعة طلب المرتجع.');
    }
}
