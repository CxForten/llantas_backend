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
        Schema::create('cash_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('opened_by')->nullable()->constrained('users');
            $table->foreignId('closed_by')->nullable()->constrained('users');

            $table->timestamp('opened_at');
            $table->timestamp('closed_At')->nullable();

            $table->unsignedBigInteger('opening_cents')->default(0);
            $table->unsignedBigInteger('expected_cents')->default(0);
            $table->unsignedBigInteger('counted_cents')->nullable();
            $table->bigInteger('difference_cents')->nullable();

            $table->string('status', 20)->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status'], 'cash_sessions_business_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_sessions');
    }
};
