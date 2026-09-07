<?php

namespace App\Models\DataEntry;

use App\Models\Settings\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\AuthorizedCompanies;

class FoodPurchaseItems extends Model
{
    use HasFactory, AuthorizedCompanies;
    
    protected $table = 'food_purchase_items';

    protected $fillable = [
        'food_purchase_id',
        'company_id',
        'food_product_purchase',
        'paper_supplies',
        'smallware_supplies',
        'cleaning_supplies',
        'pepsi',
        'total_amount',
    ];

    protected $casts = [
        'food_product_purchase' => 'decimal:2',
        'paper_supplies' => 'decimal:2',
        'smallware_supplies' => 'decimal:2',
        'cleaning_supplies' => 'decimal:2',
        'pepsi' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function foodPurchase()
    {
        return $this->belongsTo(FoodPurchase::class, 'food_purchase_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id')->selectRaw('id, CONCAT(store_number, " - ", name) as name, store_number,state_id');
    }
}