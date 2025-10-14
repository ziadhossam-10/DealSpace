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
        Schema::table('email_accounts', function (Blueprint $table) {
            $table->string('webhook_subscription_id')->nullable();
            $table->timestamp('webhook_expires_at')->nullable();
            $table->timestamp('webhook_registered_at')->nullable();
            $table->string('webhook_history_id')->nullable(); // For Gmail
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_accounts', function (Blueprint $table) {
            //
        });
    }
};
