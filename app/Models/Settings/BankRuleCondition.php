<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankRuleCondition extends Model
{
    use HasFactory;

    protected $table = 'bank_rule_conditions';

    protected $fillable = [
        'bank_rule_id',
        'condition_type',
        'value',
    ];

    public function bankRule()
    {
        return $this->belongsTo(BankRule::class);
    }
}
