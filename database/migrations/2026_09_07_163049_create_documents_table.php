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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();

            $table->string('type', 30)->default('factura');
            $table->string('estab', 3);
            $table->string('pto_emision', 3);
            $table->string('sequential',9);
            $table->string('full_number', 20);

            $table->string('access_key', 49)->nullable();
            $table->string('auth_number', 50)->nullable();
            $table->timestamp('authorized_at')->nullable();

            $table->string('status', 20)->default('pendiente');

            $table->string('xml_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('attempts')->default(0);

            $table->timestamps();

            $table->unique(['business_id', 'full_number'], 'documents_business_full_number_unique');
            $table->index(['business_id', 'status'], 'documents_business_status_idx');
            $table->index(['status', 'attempts'], 'documents_retry_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
