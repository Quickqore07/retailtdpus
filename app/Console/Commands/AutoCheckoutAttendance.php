<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\Settings\Office;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoCheckoutAttendance extends Command
{
    protected $signature = 'attendance:auto-checkout';

    protected $description = 'Automatically check out employees who forgot to check out, using their checkout_time (default 6:00 PM)';

    public function handle(): int
    {
        $this->info('Starting attendance auto-checkout...');
        Log::info('Attendance auto-checkout started');

        $openAttendances = Attendance::query()
            ->with(['user.office', 'breaks'])
            ->whereNull('check_out')
            ->get();

        if ($openAttendances->isEmpty()) {
            $this->info('No open attendance records found.');
            Log::info('Attendance auto-checkout completed - no records to process');

            return self::SUCCESS;
        }

        $this->info("Found {$openAttendances->count()} open attendance record(s).");
        $successCount = 0;
        $failureCount = 0;

        foreach ($openAttendances as $attendance) {
            try {
                $this->autoCheckout($attendance);
                $successCount++;
                $this->info("Auto-checked out attendance #{$attendance->id} for user #{$attendance->employee_id}.");
            } catch (\Throwable $e) {
                $failureCount++;
                $this->error("Failed to auto-check out attendance #{$attendance->id}: {$e->getMessage()}");
                Log::error('Attendance auto-checkout failed', [
                    'attendance_id' => $attendance->id,
                    'employee_id' => $attendance->employee_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Auto-checkout finished. Success: {$successCount}, Failed: {$failureCount}.");
        Log::info('Attendance auto-checkout completed', [
            'success' => $successCount,
            'failed' => $failureCount,
        ]);

        return $failureCount > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function autoCheckout(Attendance $attendance): void
    {
        $checkOut = $this->resolveCheckoutTime($attendance);

        DB::transaction(function () use ($attendance, $checkOut) {
            $breakMinutes = (int) $attendance->break_minutes;

            $openBreak = $attendance->breaks
                ->first(fn (AttendanceBreak $break) => $break->end_time === null);

            if ($openBreak) {
                $openBreak->update([
                    'end_time' => $checkOut,
                ]);

                $breakMinutes += (int) round(max(0, $openBreak->start_time->diffInMinutes($checkOut)));
            }

            $workMinutes = (int) round(max(0, $attendance->check_in->diffInMinutes($checkOut) - $breakMinutes));

            $attendance->update([
                'check_out' => $checkOut,
                'work_minutes' => $workMinutes,
                'break_minutes' => $breakMinutes,
                'status' => Attendance::STATUS_CHECKED_OUT,
            ]);
        });
    }

    private function resolveCheckoutTime(Attendance $attendance): Carbon
    {
        $checkoutTime = $attendance->user?->checkout_time;
        $timezone = $attendance->user?->office?->resolvedTimezone() ?: Office::DEFAULT_TIMEZONE;

        // Fall back to 18:00 in the office timezone when checkout_time is not set.
        if (empty($checkoutTime)) {
            $checkoutTime = '18:00';
        } else {
            $checkoutTime = substr((string) $checkoutTime, 0, 5);
        }

        $checkInDate = $attendance->check_in->copy()->timezone($timezone)->toDateString();
        $checkOut = Carbon::parse("{$checkInDate} {$checkoutTime}:00", $timezone)->utc();

        // Ensure checkout is not before check-in (e.g. overnight / misconfigured time).
        if ($checkOut->lt($attendance->check_in)) {
            $checkOut = $attendance->check_in->copy();
        }

        return $checkOut;
    }
}
