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
        Schema::create('account_securities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->uuid('security_id');
            $table->timestamps();

            // Indexes for query performance
            $table->index('account_id');
            $table->index('security_id');

            // Prevent duplicate account ↔ security pairs
            $table->unique(['account_id', 'security_id'], 'account_securities_unique');

            // Cascade deletes: removing an account or security removes pivot rows
            $table->foreign('account_id')
                ->references('id')->on('accounts')
                ->onDelete('cascade');

            $table->foreign('security_id')
                ->references('id')->on('securities')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_securities');
    }
};
