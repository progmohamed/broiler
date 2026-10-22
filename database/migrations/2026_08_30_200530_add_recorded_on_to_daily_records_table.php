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
        Schema::table('daily_records', function (Blueprint $table) {
            $table->date('recorded_on')->nullable()->after('id');
        });

        DB::table('daily_records')->update([
            'recorded_on' => DB::raw('DATE(created_at)'),
        ]);

        Schema::table('daily_records', function (Blueprint $table) {
            $table->date('recorded_on')->nullable(false)->change();
            $table->unique('recorded_on');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_records', function (Blueprint $table) {
            $table->dropUnique(['recorded_on']);
            $table->dropColumn('recorded_on');
        });
    }
};
