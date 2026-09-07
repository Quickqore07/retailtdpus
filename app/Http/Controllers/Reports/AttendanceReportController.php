<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\Settings\Office;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceReportController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('access', 'attendance-report.index');

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'report_type' => 'nullable|in:summary,detailed',
            'employee_id' => 'nullable|integer|exists:users,id',
        ]);
        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();
        $reportType = $request->input('report_type', 'summary');

        $records = Attendance::query()
            ->with(['user:id,name,user_code,office_id', 'user.office:id,timezone', 'breaks'])
            ->whereBetween('check_in', [$startDate, $endDate])
            ->when($request->filled('employee_id'), function ($query) use ($request) {
                $query->where('employee_id', $request->employee_id);
            })
            ->orderBy('check_in')
            ->get();

        $rows = $records->map(function (Attendance $attendance) use ($reportType) {
            return $this->formatAttendanceRow($attendance, $reportType);
        })->values();

        return to_json([
            'data' => $rows,
            'report_type' => $reportType,
            'totals' => [
                'records' => $rows->count(),
                'work_minutes' => (int) $rows->sum('work_minutes'),
                'break_minutes' => (int) $rows->sum('break_minutes'),
                'work_hours' => round(((int) $rows->sum('work_minutes')) / 60, 2),
                'break_hours' => round(((int) $rows->sum('break_minutes')) / 60, 2),
            ],
        ]);
    }

    public function updateWorkHours(Request $request, Attendance $attendance)
    {
        $this->authorize('access', 'attendance-report.update');

        $request->validate([
            'work_hours' => 'required|numeric|min:0|max:48',
        ]);

        if (!$attendance->check_in) {
            return to_json([
                'message' => 'Attendance has no check-in time.',
            ], 422);
        }

        if ($attendance->status === Attendance::STATUS_ON_BREAK) {
            return to_json([
                'message' => 'End the active break before updating work hours.',
            ], 422);
        }

        $workMinutes = (int) round((float) $request->work_hours * 60);
        $breakMinutes = (int) $attendance->break_minutes;
        $checkOut = $attendance->check_in->copy()->addMinutes($workMinutes + $breakMinutes);

        $attendance->update([
            'work_minutes' => $workMinutes,
            'check_out' => $checkOut,
            'status' => Attendance::STATUS_CHECKED_OUT,
        ]);

        $attendance->load(['user:id,name,user_code,office_id', 'user.office:id,timezone', 'breaks']);

        return to_json([
            'saved' => true,
            'message' => 'Work hours updated successfully.',
            'row' => $this->formatAttendanceRow($attendance, 'detailed'),
        ]);
    }

    public function updateBreakHours(Request $request, AttendanceBreak $attendanceBreak)
    {
        $this->authorize('access', 'attendance-report.update');

        $request->validate([
            'break_hours' => 'required|numeric|min:0|max:12',
        ]);

        $attendance = $attendanceBreak->attendance;
        if (!$attendance) {
            return to_json([
                'message' => 'Attendance record not found for this break.',
            ], 422);
        }

        if (!$attendanceBreak->start_time) {
            return to_json([
                'message' => 'Break has no start time.',
            ], 422);
        }

        $breakMinutes = (int) round((float) $request->break_hours * 60);
        $endTime = $attendanceBreak->start_time->copy()->addMinutes($breakMinutes);

        $attendanceBreak->update([
            'end_time' => $endTime,
        ]);

        $attendance->load('breaks');
        $totalBreakMinutes = $this->sumBreakMinutes($attendance);

        $updateData = [
            'break_minutes' => $totalBreakMinutes,
        ];

        if ($attendance->status === Attendance::STATUS_ON_BREAK) {
            $hasOpenBreak = $attendance->breaks->contains(fn (AttendanceBreak $break) => $break->end_time === null);
            if (!$hasOpenBreak) {
                $updateData['status'] = Attendance::STATUS_CHECKED_IN;
            }
        }

        if ($attendance->check_out || $attendance->status === Attendance::STATUS_CHECKED_OUT) {
            $workMinutes = (int) $attendance->work_minutes;
            if ($attendance->check_in) {
                $updateData['check_out'] = $attendance->check_in->copy()->addMinutes($workMinutes + $totalBreakMinutes);
            }
            $updateData['status'] = Attendance::STATUS_CHECKED_OUT;
        }

        $attendance->update($updateData);
        $attendance->load(['user:id,name,user_code,office_id', 'user.office:id,timezone', 'breaks']);

        return to_json([
            'saved' => true,
            'message' => 'Break hours updated successfully.',
            'row' => $this->formatAttendanceRow($attendance, 'detailed'),
        ]);
    }

    private function sumBreakMinutes(Attendance $attendance): int
    {
        return (int) $attendance->breaks->sum(function (AttendanceBreak $break) {
            if (!$break->start_time || !$break->end_time) {
                return 0;
            }

            return (int) round(max(0, $break->start_time->diffInMinutes($break->end_time)));
        });
    }

    private function formatAttendanceRow(Attendance $attendance, string $reportType = 'summary'): array
    {
        $workMinutes = $this->resolveWorkMinutes($attendance);
        $breakMinutes = (int) $attendance->break_minutes;
        $timezone = $attendance->user?->office?->resolvedTimezone() ?: Office::DEFAULT_TIMEZONE;

        if ($attendance->status === Attendance::STATUS_ON_BREAK) {
            $activeBreak = $attendance->breaks->first(fn (AttendanceBreak $break) => $break->end_time === null);
            if ($activeBreak?->start_time) {
                $breakMinutes += (int) round(max(0, $activeBreak->start_time->diffInMinutes(now())));
            }
        }

        $row = [
            'id' => $attendance->id,
            'employee_name' => $attendance->user?->name,
            'employee_code' => $attendance->user?->user_code,
            'timezone' => $timezone,
            'date' => $attendance->check_in?->timezone($timezone)->toDateString(),
            'check_in' => optional($attendance->check_in)->toIso8601String(),
            'check_out' => optional($attendance->check_out)->toIso8601String(),
            'status' => $attendance->status,
            'work_minutes' => $workMinutes,
            'break_minutes' => $breakMinutes,
            'work_hours' => round($workMinutes / 60, 2),
            'break_hours' => round($breakMinutes / 60, 2),
        ];

        if ($reportType === 'detailed') {
            $row['breaks'] = $attendance->breaks
                ->sortBy('start_time')
                ->values()
                ->map(function (AttendanceBreak $break, int $index) {
                    $end = $break->end_time ?: now();
                    $minutes = $break->start_time
                        ? (int) round(max(0, $break->start_time->diffInMinutes($end)))
                        : 0;

                    return [
                        'id' => $break->id,
                        'index' => $index + 1,
                        'start_time' => optional($break->start_time)->toIso8601String(),
                        'end_time' => optional($break->end_time)->toIso8601String(),
                        'minutes' => $minutes,
                        'hours' => round($minutes / 60, 2),
                        'is_open' => $break->end_time === null,
                    ];
                });
        }

        return $row;
    }

    private function resolveWorkMinutes(Attendance $attendance): int
    {
        if ($attendance->status === Attendance::STATUS_CHECKED_OUT) {
            return (int) $attendance->work_minutes;
        }

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
}
