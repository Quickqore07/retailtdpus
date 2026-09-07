<?php

namespace App\Models\DataEntry;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Support\AuthorizedCompanies;
class SalesProjection extends Model
{
    use HasFactory, AuthorizedCompanies;

    protected $table = 'sales_projections';

    protected $fillable = [
        'year',
        'start_date',
        'end_date',
        'company_id',
        'sales_projection',
        'ideal_food_projection',
        'ideal_cost_percent',
        'created_by',
        'updated_by',
    ];
    protected $searchableColumns = [
        'year',
        'start_date',
        'end_date',
        'sales_projection',
        'ideal_food_projection',
        'ideal_cost_percent',
    ];
    protected $casts = [
        'year' => 'integer',
        'company_id' => 'integer',
        'sales_projection' => 'decimal:2',
        'ideal_food_projection' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->created_by)) {
                $model->created_by = Auth::id();
            }
            if (empty($model->updated_by)) {
                $model->updated_by = Auth::id();
            }
        });

        static::updating(function ($model) {
            if (empty($model->updated_by)) {
                $model->updated_by = Auth::id();
            }
        });
    }
}
