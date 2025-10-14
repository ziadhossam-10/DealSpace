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
        Schema::table('calendar_events', function (Blueprint $table) {
            // Add polymorphic relationship columns
            $table->string('syncable_type')->nullable()->after('crm_meeting_id');
            $table->unsignedBigInteger('syncable_id')->nullable()->after('syncable_type');

            // Add event type column
            $table->enum('event_type', ['event', 'appointment', 'task'])->default('event')->after('syncable_id');

            // Add index for polymorphic relationship
            $table->index(['syncable_type', 'syncable_id']);

            // Add index for event type
            $table->index('event_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};