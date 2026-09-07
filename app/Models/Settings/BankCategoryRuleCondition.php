<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankCategoryRuleCondition extends Model
{
    use HasFactory;

    protected $table = 'bank_category_rule_conditions';

    protected $fillable = [
        'bank_category_rule_id',
        'condition_type',
        'value',
    ];

    public function bankCategoryRule()
    {
        return $this->belongsTo(BankCategoryRule::class);
    }
}
