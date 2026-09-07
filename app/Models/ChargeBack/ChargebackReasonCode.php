<?php

namespace App\Models\ChargeBack;

use App\Models\Traits\LogsActivity;
use App\Models\User;
use App\Support\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ChargebackReasonCode extends Model
{
    use HasFactory, Filterable, LogsActivity;

    protected $table = 'chargeback_reason_codes';

    protected $fillable = [
        'code',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $activityFields = [
        ['field' => 'code', 'label' => 'Code', 'type' => 'string'],
        ['field' => 'description', 'label' => 'Description', 'type' => 'string'],
        ['field' => 'count', 'label' => 'Count', 'type' => 'number'],
        ['field' => 'date_from', 'label' => 'Date From', 'type' => 'date'],
        ['field' => 'date_to', 'label' => 'Date To', 'type' => 'date'],
        ['field' => 'dates', 'label' => 'Dates', 'type' => 'array'],
        ['field' => 'filename', 'label' => 'Filename', 'type' => 'string'],
        ['field' => 'source', 'label' => 'Source', 'type' => 'string'],
        ['field' => 'imported_at', 'label' => 'Imported At', 'type' => 'string'],
        ['field' => 'ids', 'label' => 'Record IDs', 'type' => 'array'],
    ];

    protected $casts = [
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    protected $sortable = [
        'code',
        'description',
        'created_at',
        'updated_at',
    ];

    protected $searchable = [
        'code',
        'description',
    ];

    protected $allowedFilters = [
        'code',
        'description',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by',
    ];

    protected $searchableColumns = [
        'code',
        'description',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->select('id', 'name');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by')->select('id', 'name');
    }

    public function chargebacks()
    {
        return $this->hasMany(Chargeback::class, 'reason_code', 'code');
    }

    public static function validCodes(): array
    {
        return static::query()->pluck('code')->all();
    }

    public static function labelsMap(): array
    {
        return static::query()->pluck('description', 'code')->all();
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
