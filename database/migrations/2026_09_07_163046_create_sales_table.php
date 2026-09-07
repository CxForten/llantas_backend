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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('cash_session_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            
            $table->string('number', 20);
            $table->dateTime('sold_at')->nullable();

            $table->string('customer_name')->default('Consumidor Final');
            $table->string('customer_ident', 20)->default('9999999999999');
            $table->string('customer_email')->nullable();

            $table->unsignedBigInteger('cost_total_cents')->default(0);
            $table->unsignedBigInteger('subtotal_cents')->default(0);
            $table->unsignedBigInteger('discount_cents')->default(0);
            $table->unsignedBigInteger('card_fee_cents')->default(0);
            $table->unsignedBigInteger('iva_cents')->default(0);
            $table->unsignedBigInteger('total_cents')->default(0);

            $table->unsignedBigInteger('business_income_cents')->default(0);
            $table->bigInteger('gross_margin_cents')->default(0);

            $table->unsignedInteger('margin_ptc')->default(25);
            $table->string('payment_method', 20)->default('efectivo');
            $table->string('doc_type', 30)->default('consumidor_final');
            $table->string('status', 20)->default('completada');

            $table->boolean('total_overridden')->default(false);
            $table->text('override_reason')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'number'], 'sales_business_number_unique');
            $table->index(['business_id', 'sold_at'], 'sales_business_date_idx');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
