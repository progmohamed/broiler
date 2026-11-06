<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_transfers', function (Blueprint $t): void {
            $t->id();
            $t->uuid('request_id')->unique();
            $t->unsignedBigInteger('medicine_id');
            $t->string('medicine_name');
            $t->unsignedInteger('quantity');
            $t->string('destination_id');
            $t->string('destination');
            $t->unsignedBigInteger('expected_total');
            $t->unsignedBigInteger('store_issue_id')->nullable()->unique();
            $t->string('status')->default('draft');
            $t->text('last_error')->nullable();
            $t->timestamps();
        });
        Schema::table('broiler_entries', function (Blueprint $t): void {
            $t->foreignId('medicine_transfer_id')->nullable()->unique()->constrained('medicine_transfers');
        });
    }

    public function down(): void
    {
        Schema::table('broiler_entries', function (Blueprint $t): void {
            $t->dropConstrainedForeignId('medicine_transfer_id');
        });
        Schema::dropIfExists('medicine_transfers');
    }
};
