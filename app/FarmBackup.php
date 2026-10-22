<?php

namespace App;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use JsonException;

class FarmBackup
{
    /** @var array<string, list<string>> */
    private const TABLE_COLUMNS = [
        'farm_settings' => ['id', 'farm_name', 'herd_birth_date', 'total_herd_count', 'app_pin', 'created_at', 'updated_at'],
        'daily_records' => ['id', 'recorded_on', 'production', 'feed', 'deaths', 'medicines', 'notes', 'created_at', 'updated_at'],
        'feed_deliveries' => ['id', 'delivered_on', 'feed_type', 'weight_tons', 'price_per_ton', 'shipping_cost', 'tip', 'supplier', 'payment_method', 'invoice_number', 'notes', 'created_at', 'updated_at'],
        'egg_sales' => ['id', 'sold_on', 'cartons', 'price_per_carton', 'buyer', 'payment_status', 'invoice_number', 'notes', 'created_at', 'updated_at'],
        'other_sales' => ['id', 'sold_on', 'product_type', 'custom_product', 'quantity', 'unit', 'unit_price', 'buyer', 'payment_status', 'invoice_number', 'notes', 'created_at', 'updated_at'],
        'worker_advances' => ['id', 'advanced_on', 'worker_name', 'amount', 'payment_method', 'notes', 'created_at', 'updated_at'],
        'expenses' => ['id', 'feed_delivery_id', 'worker_advance_id', 'spent_on', 'category', 'amount', 'payment_method', 'payee', 'invoice_number', 'notes', 'created_at', 'updated_at'],
    ];

    /** @return array<string, mixed> */
    public function exportData(): array
    {
        $tables = [];

        foreach (self::TABLE_COLUMNS as $table => $columns) {
            $tables[$table] = DB::table($table)->orderBy('id')->get($columns)->map(fn (object $row): array => (array) $row)->all();
        }

        foreach ($tables['farm_settings'] as &$setting) {
            unset($setting['app_pin']);
        }
        unset($setting);

        return [
            'application' => 'layer-farm-management',
            'version' => 1,
            'created_at' => now()->toIso8601String(),
            'tables' => $tables,
        ];
    }

    /** @return array<string, int> */
    public function recordCounts(): array
    {
        return collect(array_keys(self::TABLE_COLUMNS))
            ->mapWithKeys(fn (string $table): array => [$table => DB::table($table)->count()])
            ->all();
    }

    /**
     * @throws JsonException
     * @throws InvalidArgumentException
     */
    public function restore(UploadedFile $backup): void
    {
        $contents = file_get_contents($backup->getRealPath());

        if ($contents === false) {
            throw new InvalidArgumentException('تعذر قراءة ملف النسخة الاحتياطية.');
        }

        $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        if (! is_array($payload) || ($payload['application'] ?? null) !== 'layer-farm-management' || ($payload['version'] ?? null) !== 1 || ! is_array($payload['tables'] ?? null)) {
            throw new InvalidArgumentException('ملف النسخة الاحتياطية غير صالح لهذا التطبيق.');
        }

        $preparedTables = $this->prepareTables($payload['tables']);
        $currentPin = DB::table('farm_settings')->value('app_pin');

        if (isset($preparedTables['farm_settings'][0])) {
            $preparedTables['farm_settings'][0]['app_pin'] = $currentPin;
        }
        $deleteOrder = ['expenses', 'worker_advances', 'other_sales', 'egg_sales', 'feed_deliveries', 'daily_records', 'farm_settings'];

        Schema::disableForeignKeyConstraints();

        try {
            DB::transaction(function () use ($preparedTables, $deleteOrder): void {
                foreach ($deleteOrder as $table) {
                    DB::table($table)->delete();
                }

                foreach (array_keys(self::TABLE_COLUMNS) as $table) {
                    foreach (array_chunk($preparedTables[$table], 200) as $rows) {
                        DB::table($table)->insert($rows);
                    }
                }
            });
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    /**
     * @param  array<string, mixed>  $tables
     * @return array<string, list<array<string, mixed>>>
     */
    private function prepareTables(array $tables): array
    {
        $prepared = [];

        foreach (self::TABLE_COLUMNS as $table => $columns) {
            if (! isset($tables[$table]) || ! is_array($tables[$table])) {
                throw new InvalidArgumentException('النسخة الاحتياطية ناقصة أو تالفة.');
            }

            $prepared[$table] = [];

            foreach ($tables[$table] as $row) {
                if (! is_array($row)) {
                    throw new InvalidArgumentException('النسخة الاحتياطية تحتوي على بيانات غير صالحة.');
                }

                $filteredRow = array_intersect_key($row, array_flip($columns));

                if (! isset($filteredRow['id'])) {
                    throw new InvalidArgumentException('النسخة الاحتياطية تحتوي على سجل بلا معرّف.');
                }

                $prepared[$table][] = $filteredRow;
            }
        }

        return $prepared;
    }
}
