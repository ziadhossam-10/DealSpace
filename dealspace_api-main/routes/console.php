<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('tokens:refresh')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduled-commands.log'));


Schedule::command('calendar-tokens:refresh')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduled-commands.log'));

Schedule::command('google-tasks:sync')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduled-commands.log'));
