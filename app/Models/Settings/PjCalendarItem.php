<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PjCalendarItem extends Model
{
    use HasFactory;

    protected $table = 'pj_calendar_items';

    protected $fillable = [
        'pj_calendar_id',
        'label',
        'weeks',
    ];

    protected $casts = [
        'weeks' => 'array',
    ];

    public function pjCalendar()
    {
        return $this->belongsTo(PjCalendar::class);
    }
}
