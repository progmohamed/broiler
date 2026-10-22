<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broiler_houses', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('capacity');
            $table->timestamps();
        });
        Schema::create('broiler_cycles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('broiler_house_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->date('started_on');
            $table->unsignedInteger('initial_birds');
            $table->decimal('initial_weight_g', 10, 2);
            $table->decimal('chick_cost', 14, 2);
            $table->date('closed_on')->nullable();
            $table->timestamps();
            $table->index(['broiler_house_id', 'closed_on']);
        });
        Schema::create('broiler_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('broiler_cycle_id')->constrained()->restrictOnDelete();
            $table->string('kind', 20);
            $table->date('recorded_on');
            $table->date('daily_on')->nullable();
            $table->unsignedInteger('birds')->default(0);
            $table->decimal('feed_kg', 14, 3)->default(0);
            $table->decimal('weight_kg', 14, 3)->default(0);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('amount', 14, 2)->default(0);
            $table->decimal('paid', 14, 2)->default(0);
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['broiler_cycle_id', 'daily_on']);
            $table->index(['broiler_cycle_id', 'recorded_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broiler_entries');
        Schema::dropIfExists('broiler_cycles');
        Schema::dropIfExists('broiler_houses');
    }
};
