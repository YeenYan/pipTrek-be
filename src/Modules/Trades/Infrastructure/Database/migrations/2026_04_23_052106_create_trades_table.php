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
        Schema::create('trades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignUuid('pair_id')->constrained('pairs')->cascadeOnDelete();
            $table->enum('trade_type', ['buy', 'sell']);
            $table->decimal('lot_size', 10, 2);
            $table->decimal('entry_price', 15, 5);
            $table->decimal('exit_price', 15, 5);
            $table->decimal('stop_loss', 15, 5);
            $table->decimal('take_profit', 15, 5);
            $table->decimal('profit_loss_amount', 15, 2);
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->enum('outcome', ['win', 'lose', 'breakeven']);
            $table->dateTime('trade_datetime');
            $table->text('notes')->nullable();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
