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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('type', 20);
            $table->integer('qty');
            $table->integer('stock_before');
            $table->integer('stock_after');
            $table->unsignedBigInteger('unit_cost_cents')->default(0);

            $table->string('reference_type', 40)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'product_id', 'user_id', 'created_at'], 'kardex_lookup_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
