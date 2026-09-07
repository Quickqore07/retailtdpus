<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MinimumWageItem extends Model
{
    use HasFactory;

    protected $table = 'minimum_wage_items';

    protected $fillable = [
        'minimum_wage_id',
        'effective_date',
        'minimum_wage',
        'tipped_minimum_wage',
    ];

    protected $casts = [
        'minimum_wage' => 'decimal:2',
        'tipped_minimum_wage' => 'decimal:2',
    ];

    public function minimumWage()
    {
        return $this->belongsTo(MinimumWage::class);
    }
}
