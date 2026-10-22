<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('worker_advances', function (Blueprint $table) {
            $table->id();
            $table->date('advanced_on')->index();
            $table->string('worker_name', 150)->index();
            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 30);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('worker_advance_id')
                ->nullable()
                ->unique()
                ->after('feed_delivery_id')
                ->constrained()
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('worker_advance_id');
        });

        Schema::dropIfExists('worker_advances');
    }
};
