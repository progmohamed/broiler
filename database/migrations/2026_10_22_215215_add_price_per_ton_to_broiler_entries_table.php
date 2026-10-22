<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('broiler_entries', function (Blueprint $table): void {
            $table->decimal('price_per_ton', 14, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('broiler_entries', function (Blueprint $table): void {
            $table->dropColumn('price_per_ton');
        });
    }
};
