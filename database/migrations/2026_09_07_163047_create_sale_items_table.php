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
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('sku', 40);
            $table->string('name');
            $table->string('category_name', 80)->nullable();
            $table->string('spec', 120)->nullable();
            $table->string('brand', 80)->nullable();

            $table->integer('qty');
            $table->unsignedBigInteger('unit_cost_cents');
            $table->unsignedBigInteger('unit_price_cents');
            $table->unsignedBigInteger('line_discount__cents')->default(0);
            $table->unsignedInteger('iva_rate')->default(15);
            $table->unsignedBigInteger('iva_cents')->default(0);
            $table->unsignedBigInteger('line_total_cents');

            $table->timestamps();
            $table->index('sale_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
