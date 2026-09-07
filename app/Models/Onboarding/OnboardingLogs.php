<?php

namespace App\Models\Onboarding;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingLogs extends Model
{
    use HasFactory;
    protected $table = 'onboarding_log';
    protected $fillable = [
        'onboarding_list_id',
        'event',
        'data',
        'description',
        'date',
    ];

    protected $casts = [
        'data' => 'array',
        'date' => 'datetime',
    ];

    public function onboardingList()
    {
        return $this->belongsTo(OnboardingList::class, 'onboarding_list_id');
    }
}