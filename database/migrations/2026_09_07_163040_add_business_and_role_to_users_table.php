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
    Schema::table('users', function (Blueprint $table) {
        $table->foreignId('business_id')->nullable()->after('id')
            ->constrained()->cascadeOnDelete();
        $table->foreignId('role_id')->nullable()->after('business_id')
            ->constrained()->nullOnDelete();   
        $table->string('pin', 100)->nullable()->after('password');
        $table->boolean('active')->default(true);  
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropForeign(['business_id']);
        $table->dropForeign(['role_id']);
        $table->dropColumn(['business_id', 'role_id', 'pin', 'active']);
    });
}
};
