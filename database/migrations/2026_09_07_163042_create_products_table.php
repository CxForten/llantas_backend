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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
    
            $table->string('sku',40);
            $table->string('name');
            $table->string('brand', 80)->nullable();
            $table->string('spec',120)->nullable();
            $table->text('description')->nullable();
            
            $table->unsignedBigInteger('cost_price')->default(0);
            $table->unsignedBigInteger('price_cents')->default(0);
            $table->unsignedBigInteger('price_alt_cents')->default(0);
            $table->unsignedBigInteger('margin_pct')->default(25);
            
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(0);
            $table->boolean('track_stock')->default(true);
            
            $table->unsignedInteger('iva_rate')->default(15);
            $table->boolean('active')->default(true);
            
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id','sku'], 'products_business_sku_unique');
            $table->index(['business_id','category_id'], 'products_business_category_idx');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
