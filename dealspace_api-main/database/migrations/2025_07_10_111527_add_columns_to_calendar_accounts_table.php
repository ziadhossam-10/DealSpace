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
        Schema::table('calendar_accounts', function (Blueprint $table) {
            $table->timestamp('tasks_last_sync_at')->nullable()->after('last_sync_at');
            $table->integer('tasks_sync_frequency')->default(15)->after('tasks_last_sync_at')->comment('Minutes between task syncs');
            $table->boolean('enable_task_sync')->default(true)->after('tasks_sync_frequency');
            $table->text('sync_errors')->nullable()->after('enable_task_sync');
            $table->timestamp('last_successful_sync_at')->nullable()->after('sync_errors');

            $table->index(['tasks_last_sync_at', 'tasks_sync_frequency'], 'idx_task_sync_schedule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendar_accounts', function (Blueprint $table) {
            //
        });
    }
};