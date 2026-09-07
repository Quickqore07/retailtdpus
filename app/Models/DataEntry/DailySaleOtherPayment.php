<?php

namespace App\Models\DataEntry;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailySaleOtherPayment extends Model
{
    use HasFactory;

    protected $table = 'daily_sales_other_payments';

    protected $fillable = [
        'daily_sale_id',
        'expense',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function dailySale()
    {
        return $this->belongsTo(DailySale::class, 'daily_sale_id');
    }
}
