<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule daily backup at 2:00 AM
Schedule::command('backup:create')
    ->dailyAt('02:00')
    ->timezone('America/New_York') // Change this to your timezone
    ->onSuccess(function () {
        Log::info('Daily backup completed successfully');
    })
    ->onFailure(function () {
        Log::error('Daily backup failed');
    });

// Schedule WorkBright status sync at 12:00 AM (midnight)
Schedule::command('workbright:sync-status')
    ->dailyAt('00:00')
    ->timezone('America/New_York') // Change this to your timezone
    ->onSuccess(function () {
        Log::info('WorkBright status sync completed successfully');
    })
    ->onFailure(function () {
        Log::error('WorkBright status sync failed');
    });

// Auto-checkout employees who forgot to check out at 12:00 AM (midnight)
Schedule::command('attendance:auto-checkout')
    ->dailyAt('00:00')
    ->timezone('America/New_York')
    ->onSuccess(function () {
        Log::info('Attendance auto-checkout completed successfully');
    })
    ->onFailure(function () {
        Log::error('Attendance auto-checkout failed');
    });
