<?php

namespace App\Models\Onboarding;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DirectDeposit extends Model
{
    use HasFactory;

    protected $table = 'direct_deposits';

    protected $fillable = [
        'onboarding_list_id',
        'company_code',
        'acct_type_1',
        'bank_name_1',
        'bank_routing_1',
        'account_number_1',
        'deposit_amount_1',
        'deposit_type_1',
        'acct_type_2',
        'bank_name_2',
        'bank_routing_2',
        'account_number_2',
        'deposit_amount_2',
        'deposit_type_2',
        'signature',
        'printed_name',
        'employee_id',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Relationship with OnboardingList
     */
    public function onboardingList()
    {
        return $this->belongsTo(OnboardingList::class, 'onboarding_list_id');
    }
}
