<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('feed_deliveries', function (Blueprint $table) {
            $table->string('payment_method', 30)->default('cash')->after('supplier');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('feed_delivery_id')
                ->nullable()
                ->unique()
                ->after('id')
                ->constrained()
                ->cascadeOnDelete();
        });

        DB::table('feed_deliveries')
            ->orderBy('id')
            ->chunkById(100, function ($deliveries): void {
                foreach ($deliveries as $delivery) {
                    $notes = 'توريد '.$delivery->feed_type;

                    if ($delivery->notes !== null) {
                        $notes .= "\n".$delivery->notes;
                    }

                    DB::table('expenses')->insertOrIgnore([
                        'feed_delivery_id' => $delivery->id,
                        'spent_on' => $delivery->delivered_on,
                        'category' => 'feed',
                        'amount' => ((float) $delivery->weight_tons * (float) $delivery->price_per_ton)
                            + (float) $delivery->shipping_cost
                            + (float) $delivery->tip,
                        'payment_method' => $delivery->payment_method,
                        'payee' => $delivery->supplier,
                        'invoice_number' => $delivery->invoice_number,
                        'notes' => $notes,
                        'created_at' => $delivery->created_at,
                        'updated_at' => $delivery->updated_at,
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('expenses')->whereNotNull('feed_delivery_id')->delete();

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('feed_delivery_id');
        });

        Schema::table('feed_deliveries', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
