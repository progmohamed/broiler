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
        Schema::create('other_sales', function (Blueprint $table) {
            $table->id();
            $table->date('sold_on')->index();
            $table->string('product_type', 50);
            $table->string('custom_product', 100)->nullable();
            $table->decimal('quantity', 12, 3);
            $table->string('unit', 30);
            $table->decimal('unit_price', 12, 2);
            $table->string('buyer', 150);
            $table->string('payment_status', 20);
            $table->string('invoice_number', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['sold_on', 'product_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('other_sales');
    }
};
