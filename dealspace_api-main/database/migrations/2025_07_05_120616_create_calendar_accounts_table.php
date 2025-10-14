<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_accounts', function (Blueprint $table) {
            $table->id();

            $table->enum('provider', ['google', 'outlook'])->default('google');
            $table->string('email')->nullable();
            $table->string('calendar_id')->nullable();
            $table->string('calendar_name')->nullable();

            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();

            $table->boolean('is_active')->default(true);

            $table->string('webhook_subscription_id')->nullable();
            $table->timestamp('webhook_expires_at')->nullable();
            $table->timestamp('webhook_registered_at')->nullable();
            $table->boolean('webhook_registration_failed')->default(false);

            $table->string('webhook_channel_id')->nullable();
            $table->string('webhook_resource_id')->nullable();

            $table->text('sync_token')->nullable();
            $table->timestamp('last_sync_at')->nullable();

            $table->json('settings')->nullable();

            $table->uuid('tenant_id')->nullable()->index();
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_accounts');
    }
};
