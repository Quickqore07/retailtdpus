<?php

namespace App\Models\DataEntry;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtherDailySaleBankDeposit extends Model
{
    use HasFactory;

    protected $table = 'other_daily_sales_bank_deps';

    protected $fillable = [
        'other_daily_sale_id',
        'bank_name',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function otherDailySale()
    {
        return $this->belongsTo(OtherDailySale::class, 'other_daily_sale_id');
    }
}
