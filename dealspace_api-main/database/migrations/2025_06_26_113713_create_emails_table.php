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
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('person_id');
            $table->foreignId('email_account_id')->nullable()->constrained()->onDelete('cascade');

            $table->string('subject');
            $table->text('body');
            $table->text('body_html')->nullable();
            $table->json('headers')->nullable();
            $table->json('attachments')->nullable();

            $table->string('to_email');
            $table->string('from_email');
            $table->string('message_id')->nullable();

            $table->boolean('is_incoming')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->boolean('is_processed')->default(false);
            $table->string('status')->default('pending'); // pending, sent, failed, delivered
            $table->text('error_message')->nullable();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->uuid('tenant_id')->nullable()->index();

            $table->timestamps();

            // Foreign keys
            $table->foreign('person_id')->references('id')->on('people')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');

            // Indexes & unique constraints
            $table->index(['email_account_id', 'is_incoming']);
            $table->index(['person_id', 'is_incoming']);
            $table->unique(['message_id', 'email_account_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};
