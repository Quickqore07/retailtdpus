<?php

namespace App\Models\Onboarding;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BenefitEnrollmentDependent extends Model
{
    use HasFactory;

    protected $table = 'benefit_enrollment_dependents';

    protected $fillable = [
        'benefit_enrollment_id',
        'last_name',
        'first_name',
        'gender',
        'dob',
        'ssn',
        'relationship',
        'medical',
        'dental',
        'vision',
    ];

    protected $casts = [
        'dob' => 'date',
        'medical' => 'integer',
        'dental' => 'integer',
        'vision' => 'integer',
    ];

    public function benefitEnrollment()
    {
        return $this->belongsTo(BenefitEnrollment::class, 'benefit_enrollment_id');
    }
}
