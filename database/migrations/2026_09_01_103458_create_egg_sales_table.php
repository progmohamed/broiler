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
        Schema::create('egg_sales', function (Blueprint $table) {
            $table->id();
            $table->date('sold_on')->index();
            $table->unsignedInteger('cartons');
            $table->decimal('price_per_carton', 12, 2);
            $table->string('buyer', 150);
            $table->string('payment_status', 20)->default('paid');
            $table->string('invoice_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('egg_sales');
    }
};
