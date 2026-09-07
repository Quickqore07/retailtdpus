<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\Settings\Office;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    public function index()
    {
        return view('attendance');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'employee_code' => 'required|string|size:6',
        ]);

        $user = $this->findUserByCode($request->employee_code);
        if (!$user) {
            return to_json([
                'message' => 'Invalid employee code.',
            ], 422);
        }

        $attendance = $this->todayAttendance($user->id);

        return to_json([
            'saved' => true,
            'message' => $attendance?->status === Attendance::STATUS_CHECKED_OUT
                ? 'You have already checked out for today. You can check in again tomorrow.'
                : 'Code verified successfully.',
            'user' => $this->userPayload($user),
            'attendance' => $this->attendancePayload($attendance),
        ]);
    }

    public function status(Request $request)
    {
        $request->validate([
            'employee_code' => 'required|string|size:6',
        ]);

        $user = $this->findUserByCode($request->employee_code);
        if (!$user) {
            return to_json([
                'message' => 'Invalid employee code.',
            ], 422);
        }

        return to_json([
            'user' => $this->userPayload($user),
            'attendance' => $this->attendancePayload($this->todayAttendance($user->id)),
        ]);
    }

    public function checkIn(Request $request)
    {
        $request->validate([
            'employee_code' => 'required|string|size:6',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $user = $this->findUserByCode($request->employee_code);
        if (!$user) {
            return to_json(['message' => 'Invalid employee code.'], 422);
        }

        $todayAttendance = $this->todayAttendance($user->id);
        if ($todayAttendance) {
            if ($todayAttendance->status === Attendance::STATUS_CHECKED_OUT) {
                return to_json([
                    'message' => 'You have already checked out for today. You can check in again tomorrow.',
                    'user' => $this->userPayload($user),
                    'attendance' => $this->attendancePayload($todayAttendance),
                ], 422);
            }

            return to_json(['message' => 'You are already checked in.'], 422);
        }

        $office = $user->office;
        if (!$office || $office->latitude === null || $office->longitude === null) {
            return to_json([
                'message' => 'No office location is configured for your account. Please contact an administrator.',
                'user' => $this->userPayload($user),
                'attendance' => null,
            ], 422);
        }

        $radiusMeters = (int) ($office->authorized_radius ?: 300);
        $distance = $this->distanceInMeters(
            (float) $office->latitude,
            (float) $office->longitude,
            (float) $request->latitude,
            (float) $request->longitude
        );

        if ($distance > $radiusMeters) {
            return to_json([
                'message' => "You must be within {$radiusMeters} meters of the office to check in.",
                'user' => $this->userPayload($user),
                'attendance' => null,
            ], 422);
        }

        $attendance = Attendance::create([
            'employee_id' => $user->id,
            'check_in' => now(),
            'work_minutes' => 0,
            'break_minutes' => 0,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'ip_address' => $request->ip(),
            'device' => substr((string) $request->userAgent(), 0, 255),
            'status' => Attendance::STATUS_CHECKED_IN,
        ]);

        return to_json([
            'saved' => true,
            'message' => 'Checked in successfully.',
            'user' => $this->userPayload($user),
            'attendance' => $this->attendancePayload($attendance->fresh('breaks')),
        ]);
    }

    public function checkOut(Request $request)
    {
        $request->validate([
            'employee_code' => 'required|string|size:6',
        ]);

        $user = $this->findUserByCode($request->employee_code);
        if (!$user) {
            return to_json(['message' => 'Invalid employee code.'], 422);
        }

        $attendance = $this->openAttendance($user->id);
        if (!$attendance) {
            return to_json(['message' => 'You are not checked in.'], 422);
        }

        if ($attendance->status === Attendance::STATUS_ON_BREAK) {
            return to_json(['message' => 'Please end your break before checking out.'], 422);
        }

        $checkOut = now();
        $breakMinutes = (int) $attendance->break_minutes;
        $workMinutes = (int) round(max(0, $attendance->check_in->diffInMinutes($checkOut) - $breakMinutes));

        $attendance->update([
            'check_out' => $checkOut,
            'work_minutes' => $workMinutes,
            'break_minutes' => $breakMinutes,
            'status' => Attendance::STATUS_CHECKED_OUT,
        ]);

        return to_json([
            'saved' => true,
            'message' => 'Checked out successfully.',
            'user' => $this->userPayload($user),
            'attendance' => $this->attendancePayload($attendance->fresh('breaks')),
        ]);
    }

    public function startBreak(Request $request)
    {
        $request->validate([
            'employee_code' => 'required|string|size:6',
        ]);

        $user = $this->findUserByCode($request->employee_code);
        if (!$user) {
            return to_json(['message' => 'Invalid employee code.'], 422);
        }

        $attendance = $this->openAttendance($user->id);
        if (!$attendance) {
            return to_json(['message' => 'You must check in before starting a break.'], 422);
        }

        if ($attendance->status === Attendance::STATUS_ON_BREAK) {
            return to_json(['message' => 'You are already on break.'], 422);
        }

        DB::transaction(function () use ($attendance) {
            AttendanceBreak::create([
                'attendance_id' => $attendance->id,
                'start_time' => now(),
            ]);

            $attendance->update([
                'status' => Attendance::STATUS_ON_BREAK,
            ]);
        });

        return to_json([
            'saved' => true,
            'message' => 'Break started.',
            'user' => $this->userPayload($user),
            'attendance' => $this->attendancePayload($attendance->fresh('breaks')),
        ]);
    }

    public function endBreak(Request $request)
    {
        $request->validate([
            'employee_code' => 'required|string|size:6',
        ]);

        $user = $this->findUserByCode($request->employee_code);
        if (!$user) {
            return to_json(['message' => 'Invalid employee code.'], 422);
        }

        $attendance = $this->openAttendance($user->id);
        if (!$attendance) {
            return to_json(['message' => 'You are not checked in.'], 422);
        }

        $openBreak = $attendance->breaks()
            ->whereNull('end_time')
            ->latest('start_time')
            ->first();

        if (!$openBreak || $attendance->status !== Attendance::STATUS_ON_BREAK) {
            return to_json(['message' => 'You are not on break.'], 422);
        }

        DB::transaction(function () use ($attendance, $openBreak) {
            $endTime = now();
            $minutes = (int) round(max(0, $openBreak->start_time->diffInMinutes($endTime)));

            $openBreak->update([
                'end_time' => $endTime,
            ]);

            $attendance->update([
                'break_minutes' => (int) $attendance->break_minutes + $minutes,
                'status' => Attendance::STATUS_CHECKED_IN,
            ]);
        });

        return to_json([
            'saved' => true,
            'message' => 'Break ended.',
            'user' => $this->userPayload($user),
            'attendance' => $this->attendancePayload($attendance->fresh('breaks')),
        ]);
    }

    private function findUserByCode(string $code): ?User
    {
        return User::query()
            ->with('office')
            ->where('user_code', trim($code))
            ->where('active', 1)
            ->first();
    }

    private function openAttendance(int $userId): ?Attendance
    {
        return Attendance::query()
            ->with('breaks')
            ->where('employee_id', $userId)
            ->whereNull('check_out')
            ->whereIn('status', [Attendance::STATUS_CHECKED_IN, Attendance::STATUS_ON_BREAK])
            ->latest('check_in')
            ->first();
    }

    private function todayAttendance(int $userId): ?Attendance
    {
        $open = $this->openAttendance($userId);
        if ($open) {
            return $open;
        }

        return Attendance::query()
            ->with('breaks')
            ->where('employee_id', $userId)
            ->where('status', Attendance::STATUS_CHECKED_OUT)
            ->whereDate('check_in', now()->toDateString())
            ->latest('check_out')
            ->first();
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'user_code' => $user->user_code,
            'timezone' => $user->office?->resolvedTimezone() ?: Office::DEFAULT_TIMEZONE,
        ];
    }

    private function attendancePayload(?Attendance $attendance): ?array
    {
        if (!$attendance) {
            return null;
        }

        $activeBreak = $attendance->breaks
            ->first(fn (AttendanceBreak $break) => $break->end_time === null);

        return [
            'id' => $attendance->id,
            'status' => $attendance->status,
            'check_in' => optional($attendance->check_in)->toIso8601String(),
            'check_out' => optional($attendance->check_out)->toIso8601String(),
            'work_minutes' => (int) $attendance->work_minutes,
            'break_minutes' => (int) $attendance->break_minutes,
            'elapsed_work_minutes' => $this->elapsedWorkMinutes($attendance),
            'active_break' => $activeBreak ? [
                'id' => $activeBreak->id,
                'start_time' => optional($activeBreak->start_time)->toIso8601String(),
            ] : null,
            'breaks' => $attendance->breaks->map(fn (AttendanceBreak $break) => [
                'id' => $break->id,
                'start_time' => optional($break->start_time)->toIso8601String(),
                'end_time' => optional($break->end_time)->toIso8601String(),
            ])->values(),
        ];
    }

    private function elapsedWorkMinutes(Attendance $attendance): int
    {
        if (!$attendance->check_in) {
            return 0;
        }

        $end = $attendance->check_out ?: now();
        $breakMinutes = (int) $attendance->break_minutes;

        if ($attendance->status === Attendance::STATUS_ON_BREAK) {
            $activeBreak = $attendance->breaks->first(fn (AttendanceBreak $break) => $break->end_time === null);
            if ($activeBreak?->start_time) {
                $breakMinutes += (int) round(max(0, $activeBreak->start_time->diffInMinutes(now())));
            }
        }

        return (int) round(max(0, $attendance->check_in->diffInMinutes($end) - $breakMinutes));
    }

    private function distanceInMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $latFrom = deg2rad($lat1);
        $latTo = deg2rad($lat2);
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
