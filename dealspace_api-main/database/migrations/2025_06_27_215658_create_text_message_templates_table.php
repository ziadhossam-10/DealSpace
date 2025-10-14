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
        Schema::create('text_message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('message');
            $table->boolean('is_shared')->default(false)->comment('Indicates whether this email template should be shared with other users in the same Follow Up Boss account');
            $table->timestamps();
            // Tenant support
            $table->uuid('tenant_id')->nullable()->index();
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            // User support
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // Add indexes for better performance
            $table->index('name');
            $table->index('is_shared');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('text_message_templates');
    }
};
