<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class FundRequirementCompany extends Model
{
    use HasFactory;

    protected $table = 'fund_requirement_companies';

    protected $fillable = [
        'fund_requirement_id',
        'company_id',
        'amount',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function fundRequirement()
    {
        return $this->belongsTo(FundRequirement::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

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
