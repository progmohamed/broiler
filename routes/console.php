<?php

use App\MedicineStore;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('medicine-store:sync', function (): void {
    DB::table('medicine_transfers')->whereIn('status', ['pending', 'returning'])->orderBy('id')->eachById(function ($transfer): void {
        app(MedicineStore::class)->sync($transfer->id);
    });
    $this->info('تمت مراجعة عمليات المخزن المعلقة.');
});
Schedule::command('medicine-store:sync')->everyFourHours()->withoutOverlapping();
