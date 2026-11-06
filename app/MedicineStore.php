<?php

namespace App;

use App\Models\BroilerCycle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class MedicineStore
{
    public function call(string $method, string $path, array $payload = []): array
    {
        if (! config('medicine-store.url') || ! config('medicine-store.token')) {
            throw new \RuntimeException('لم يتم إعداد الاتصال بالمخزن بعد.');
        }
        $response = Http::baseUrl(rtrim(config('medicine-store.url'), '/').'/api/v1')->withToken(config('medicine-store.token'))->acceptJson()->connectTimeout(3)->timeout(12)->send($method, $path, ['json' => $payload]);
        if ($response->status() === 422 || $response->status() === 409) {
            throw ValidationException::withMessages(['store' => $response->json('message', 'تعذر تنفيذ الطلب. راجع الرصيد والتكلفة.')]);
        }

        return $response->throw()->json('data');
    }

    public function sync(int $id): void
    {
        $transfer = DB::table('medicine_transfers')->find($id);
        if (! $transfer || ! in_array($transfer->status, ['pending', 'returning'], true)) {
            return;
        }
        try {
            $data = $transfer->status === 'returning'
             ? $this->call('POST', 'issues/'.$transfer->request_id.'/return')
             : $this->call('POST', 'issues', ['medicine_id' => $transfer->medicine_id, 'quantity' => $transfer->quantity, 'request_id' => $transfer->request_id, 'destination_id' => $transfer->destination_id, 'destination' => $transfer->destination, 'expected_total' => $transfer->expected_total]);
            if ($data['request_id'] !== $transfer->request_id || (int) $data['quantity'] !== $transfer->quantity || (int) $data['medicine_id'] !== $transfer->medicine_id || (int) $data['total_cost'] !== $transfer->expected_total || $data['destination_id'] !== $transfer->destination_id) {
                throw new \RuntimeException('استجابة المخزن لا تطابق الطلب.');
            }
            DB::transaction(function () use ($id, $data): void {
                $t = DB::table('medicine_transfers')->where('id', $id)->lockForUpdate()->first();
                if (! in_array($t->status, ['pending', 'returning'], true)) {
                    return;
                }
                $returned = ! empty($data['returned_at']);
                if ($t->status === 'returning' && ! $returned) {
                    throw new \RuntimeException('لم يؤكد المخزن المرتجع.');
                }
                $amount = $returned ? '0.00' : number_format($data['total_cost'] / 100, 2, '.', '');
                $cycle = BroilerCycle::query()->findOrFail((int) $t->destination_id);
                $record = DB::table('broiler_entries')->where('medicine_transfer_id', $t->id)->first();
                if ($record) {
                    DB::table('broiler_entries')->where('id', $record->id)->update(['amount' => $amount, 'updated_at' => now()]);
                } else {
                    DB::table('broiler_entries')->insert(['broiler_cycle_id' => $cycle->id, 'medicine_transfer_id' => $t->id, 'kind' => 'expense', 'category' => 'medicines', 'recorded_on' => substr($t->created_at, 0, 10), 'description' => $t->medicine_name.' × '.$t->quantity.' وحدة — المخزن', 'amount' => $amount, 'paid' => 0, 'created_at' => now(), 'updated_at' => now()]);
                }
                DB::table('medicine_transfers')->where('id', $id)->update(['status' => $returned ? 'returned' : 'completed', 'store_issue_id' => $data['id'], 'last_error' => null, 'updated_at' => now()]);
            }, 5);
        } catch (ValidationException $e) {
            DB::table('medicine_transfers')->where('id', $id)->where('status', $transfer->status)->update(['status' => $transfer->status === 'pending' ? 'rejected' : 'returning', 'last_error' => $e->getMessage(), 'updated_at' => now()]);
        } catch (\Throwable $e) {
            report($e);
            DB::table('medicine_transfers')->where('id', $id)->where('status', $transfer->status)->update(['last_error' => 'تعذر تأكيد الاتصال. أعد المحاولة بنفس الطلب؛ لن يتكرر الخصم.', 'updated_at' => now()]);
        }
    }
}
