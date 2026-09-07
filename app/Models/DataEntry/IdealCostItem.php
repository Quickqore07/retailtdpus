<?php

namespace App\Models\DataEntry;

use App\Models\Settings\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\AuthorizedCompanies;
class IdealCostItem extends Model
{
    use HasFactory, AuthorizedCompanies;

    protected $table = 'ideal_cost_items';

    protected $fillable = [
        'ideal_cost_id',
        'company_id',
        'ideal_cost',
        'mileage',
        'delivery',
    ];

    protected $casts = [
        'ideal_cost' => 'decimal:2',
        'mileage' => 'decimal:2',
        'delivery' => 'decimal:2',
    ];

    public function idealCost()
    {
        return $this->belongsTo(IdealCost::class, 'ideal_cost_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id')->selectRaw('id, CONCAT(store_number, " - ", name) as name, store_number,state_id');
    }
}
