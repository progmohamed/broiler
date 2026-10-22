<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('broiler_vaccinations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('broiler_cycle_id')->constrained()->restrictOnDelete();
            $table->string('name', 150);
            $table->dateTime('due_at');
            $table->string('timezone', 100);
            $table->unsignedSmallInteger('remind_before_hours');
            $table->dateTime('remind_at');
            $table->text('notes')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            $table->index(['completed_at', 'remind_at']);
            $table->index(['broiler_cycle_id', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broiler_vaccinations');
    }
};
