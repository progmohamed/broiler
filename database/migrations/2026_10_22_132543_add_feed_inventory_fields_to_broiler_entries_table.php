<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broiler_entries', function (Blueprint $table): void {
            $table->string('category', 30)->nullable();
            $table->string('feed_type', 100)->nullable();
            $table->string('supplier', 150)->nullable();
            $table->string('invoice_number', 100)->nullable();
            $table->decimal('shipping_cost', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('broiler_entries', function (Blueprint $table): void {
            $table->dropColumn(['category', 'feed_type', 'supplier', 'invoice_number', 'shipping_cost']);
        });
    }
};
