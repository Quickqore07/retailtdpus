<?php

namespace App\Models\Onboarding;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Filterable;
use App\Models\Traits\LogsActivity;

class OnboardingHandbook extends Model
{
    use HasFactory, Filterable, LogsActivity;

    protected $table = 'onboarding_handbook';

    protected $fillable = [
        'onboarding_id',
        'employee_sign',
        'date',
        'print_full_name',
        'is_checked',
    ];

    protected $activityFields = [
        ['field' => 'onboarding_id', 'label' => 'Onboarding Id', 'type' => 'string'],
        ['field' => 'date', 'label' => 'Date', 'type' => 'date'],
        ['field' => 'print_full_name', 'label' => 'Print Full Name', 'type' => 'string'],
        ['field' => 'is_checked', 'label' => 'Is Checked', 'type' => 'boolean'],
        ['field' => 'count', 'label' => 'Count', 'type' => 'number'],
        ['field' => 'date_from', 'label' => 'Date From', 'type' => 'date'],
        ['field' => 'date_to', 'label' => 'Date To', 'type' => 'date'],
        ['field' => 'dates', 'label' => 'Dates', 'type' => 'array'],
        ['field' => 'filename', 'label' => 'Filename', 'type' => 'string'],
        ['field' => 'source', 'label' => 'Source', 'type' => 'string'],
        ['field' => 'imported_at', 'label' => 'Imported At', 'type' => 'string'],
        ['field' => 'ids', 'label' => 'Record IDs', 'type' => 'array'],
    ];

    public function onboarding()
    {
        return $this->belongsTo(OnboardingList::class, 'onboarding_id');
    }
}
