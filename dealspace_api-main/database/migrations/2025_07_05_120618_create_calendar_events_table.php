<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calendar_account_id')->constrained()->onDelete('cascade');
            $table->foreignId('person_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            $table->string('external_id')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('location')->nullable();

            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->string('timezone')->nullable();
            $table->boolean('is_all_day')->default(false);

            $table->enum('status', ['confirmed', 'tentative', 'cancelled'])->default('confirmed');
            $table->enum('visibility', ['default', 'public', 'private'])->default('default');

            $table->json('attendees')->nullable();
            $table->string('organizer_email')->nullable();
            $table->string('meeting_link')->nullable();

            $table->json('reminders')->nullable();
            $table->json('recurrence')->nullable();

            $table->enum('sync_status', ['synced', 'pending', 'failed'])->default('pending');
            $table->enum('sync_direction', ['from_external', 'to_external', 'bidirectional'])->default('bidirectional');

            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('external_updated_at')->nullable();
            $table->text('sync_error')->nullable();

            $table->unsignedBigInteger('crm_meeting_id')->nullable();

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
        Schema::dropIfExists('calendar_events');
    }
};
