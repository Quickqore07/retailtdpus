<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendance extends Model
{
    use HasFactory;

    public const STATUS_CHECKED_IN = 'checked_in';
    public const STATUS_ON_BREAK = 'on_break';
    public const STATUS_CHECKED_OUT = 'checked_out';

    protected $table = 'attendance';

    protected $fillable = [
        'employee_id',
        'check_in',
        'check_out',
        'work_minutes',
        'break_minutes',
        'latitude',
        'longitude',
        'ip_address',
        'device',
        'status',
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'work_minutes' => 'integer',
        'break_minutes' => 'integer',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    /**
     * Identified via users.user_code; employee_id stores the related user id.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function breaks(): HasMany
    {
        return $this->hasMany(AttendanceBreak::class, 'attendance_id');
    }
}
