<?php

namespace Tests\Feature;

use App\MedicineStore;
use App\Models\BroilerCycle;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class MedicineTransferTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['medicine-store.url' => 'http://store.test', 'medicine-store.token' => str_repeat('x', 40)]);
        Http::preventStrayRequests();
    }

    private function transfer(string $status = 'pending'): object
    {
        $destination = (string) BroilerCycle::factory()->create()->id;
        $id = DB::table('medicine_transfers')->insertGetId(['request_id' => (string) Str::uuid(), 'medicine_id' => 1, 'medicine_name' => 'دواء اختبار', 'quantity' => 3, 'destination_id' => $destination, 'destination' => 'عنبر الاختبار', 'expected_total' => 32100, 'status' => $status, 'created_at' => now(), 'updated_at' => now()]);

        return DB::table('medicine_transfers')->find($id);
    }

    private function response(object $t, bool $returned = false): array
    {
        return ['data' => ['id' => 77, 'medicine_id' => 1, 'request_id' => $t->request_id, 'quantity' => 3, 'destination_id' => $t->destination_id, 'total_cost' => 32100, 'returned_at' => $returned ? '2026-11-06 12:00:00' : null]];
    }

    public function test_retry_after_lost_response_records_cost_once_and_return_reverses_it(): void
    {
        $t = $this->transfer();
        Http::fake(['store.test/api/v1/issues' => Http::sequence()->pushFailedConnection()->push($this->response($t)), 'store.test/api/v1/issues/'.$t->request_id.'/return' => Http::response($this->response($t, true))]);
        app(MedicineStore::class)->sync($t->id);
        $this->assertDatabaseHas('medicine_transfers', ['id' => $t->id, 'status' => 'pending']);
        $this->assertDatabaseCount('broiler_entries', 0);
        app(MedicineStore::class)->sync($t->id);
        app(MedicineStore::class)->sync($t->id);
        $this->assertDatabaseHas('broiler_entries', ['medicine_transfer_id' => $t->id, 'amount' => 321]);
        $this->assertDatabaseCount('broiler_entries', 1);
        $this->post(route('medicine-transfers.return', $t->id), ['cycle' => $t->destination_id])->assertRedirect();
        $this->assertDatabaseHas('medicine_transfers', ['id' => $t->id, 'status' => 'returned']);
        $this->assertDatabaseHas('broiler_entries', ['medicine_transfer_id' => $t->id, 'amount' => 0]);
        Http::assertSentCount(3);
    }

    public function test_quote_does_not_charge_before_confirmation(): void
    {
        $t = $this->transfer('draft');
        DB::table('medicine_transfers')->delete();
        Http::fake(['store.test/api/v1/medicines' => Http::response(['data' => [['id' => 1, 'name' => 'دواء اختبار', 'stock' => 5]]]), 'store.test/api/v1/quotes' => Http::response(['data' => ['total_cost' => 32100]])]);
        $this->post(route('medicine-transfers.quote'), ['cycle' => $t->destination_id, 'medicine_id' => 1, 'quantity' => 3])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('medicine_transfers', ['status' => 'draft', 'expected_total' => 32100]);
        $this->assertDatabaseCount('broiler_entries', 0);
        $this->get(route('medicine-transfers.index', ['cycle' => $t->destination_id]))->assertSee('تأكيد الصرف والتكلفة');
        Http::assertSentCount(3);
    }

    public function test_rejected_quote_keeps_account_and_stock_request_unposted(): void
    {
        $t = $this->transfer();
        Http::fake(['store.test/api/v1/issues' => Http::response(['message' => 'تغيرت تكلفة المخزون.'], 422)]);
        app(MedicineStore::class)->sync($t->id);
        $this->assertDatabaseHas('medicine_transfers', ['id' => $t->id, 'status' => 'rejected']);
        $this->assertDatabaseCount('broiler_entries', 0);
    }

    public function test_linked_cost_cannot_be_deleted_manually(): void
    {
        $t = $this->transfer();
        Http::fake(['store.test/api/v1/issues' => Http::response($this->response($t))]);
        app(MedicineStore::class)->sync($t->id);
        $record = DB::table('broiler_entries')->where('medicine_transfer_id', $t->id)->first();
        $this->delete(route('broiler.entries.cost.destroy', [$t->destination_id, $record->id]))->assertUnprocessable();
        $this->assertDatabaseHas('broiler_entries', ['id' => $record->id, 'amount' => 321]);
    }

    public function test_unavailable_store_renders_retry_message(): void
    {
        $t = $this->transfer();
        Http::fake(['store.test/api/v1/medicines' => Http::failedConnection()]);
        $this->get(route('medicine-transfers.index', ['cycle' => $t->destination_id]))->assertSee('المخزن غير متاح حاليًا')->assertSee('إعادة المحاولة بنفس الطلب');
    }

    public function test_other_cycle_cannot_confirm_transfer_and_closed_cycle_cannot_start_issue(): void
    {
        $t = $this->transfer('draft');
        $other = BroilerCycle::factory()->create();
        $this->post(route('medicine-transfers.confirm', $t->id), ['cycle' => $other->id])->assertNotFound();
        BroilerCycle::query()->find($t->destination_id)->update(['closed_on' => today()]);
        $this->post(route('medicine-transfers.confirm', $t->id), ['cycle' => $t->destination_id])->assertUnprocessable();
        Http::assertNothingSent();
        $this->assertDatabaseHas('medicine_transfers',['id' => $t->id, 'status' => 'draft']);
    }
}
