<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            $table->string('broker');
            $table->string('platform')->nullable();
            $table->enum('account_mode', ['demo', 'real']);
            $table->string('account_type')->nullable();
            $table->decimal('leverage', 8, 2);
            $table->decimal('starting_balance', 15, 2);
            $table->decimal('target_amount', 15, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
