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
        Schema::create('broiler_house_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broiler_house_id')->constrained()->restrictOnDelete();
            $table->string('description', 255);
            $table->decimal('amount', 14, 2);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('broiler_house_expenses');
    }
};
