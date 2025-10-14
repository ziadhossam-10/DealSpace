<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CalendarAccount;
use App\Jobs\SyncGoogleTasksJob;
use Illuminate\Support\Carbon;

class DispatchGoogleTasksSync extends Command
{
    protected $signature = 'google-tasks:sync';
    protected $description = 'Dispatch Google Tasks sync jobs for due accounts';

    public function handle()
    {
        $now = Carbon::now();

        CalendarAccount::where('is_active', true)
            ->where('enable_task_sync', true)
            ->where(function ($query) use ($now) {
                $query
                    ->whereNull('tasks_last_sync_at')
                    ->orWhereRaw("TIMESTAMPADD(MINUTE, tasks_sync_frequency, tasks_last_sync_at) <= ?", [$now]);
            })
            ->orderBy('id') // Optional, but good for chunking
            ->chunk(50, function ($accounts) {
                foreach ($accounts as $account) {
                    SyncGoogleTasksJob::dispatch($account);
                }
            });

        $this->info('Dispatched Google Tasks sync jobs for due accounts.');
    }
}