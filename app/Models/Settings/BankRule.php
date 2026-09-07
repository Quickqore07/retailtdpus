<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Filterable;
use App\Models\Traits\LogsActivity;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
class BankRule extends Model
{
    use HasFactory, Filterable, LogsActivity;

    protected $table = 'bank_rules';

    protected $fillable = [
        'name',
        'ledger_id',
        'condition',
        'created_by',
        'updated_by',
    ];

    protected $activityFields = [
        ['field' => 'name', 'label' => 'Name', 'type' => 'string'],
        ['field' => 'ledger_id', 'label' => 'Ledger', 'table' => 'ledgers', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'condition', 'label' => 'Condition', 'type' => 'string'],
        ['field' => 'count', 'label' => 'Count', 'type' => 'number'],
        ['field' => 'date_from', 'label' => 'Date From', 'type' => 'date'],
        ['field' => 'date_to', 'label' => 'Date To', 'type' => 'date'],
        ['field' => 'dates', 'label' => 'Dates', 'type' => 'array'],
        ['field' => 'filename', 'label' => 'Filename', 'type' => 'string'],
        ['field' => 'source', 'label' => 'Source', 'type' => 'string'],
        ['field' => 'imported_at', 'label' => 'Imported At', 'type' => 'string'],
        ['field' => 'ids', 'label' => 'Record IDs', 'type' => 'array'],
    ];

    protected $sortable = [
        'name',
        'ledger_id',
        'condition',
        'created_at',
        'updated_at',
    ];
    protected $searchableColumns = [
        'name',
        'condition',
    ];
    protected $searchable = [
        'name',
        'ledger_id',
        'condition',
        'created_at',
        'updated_at',
    ];

    protected $allowedFilters = [
        'name',
        'ledger_id',
        'condition',
        'created_at',
        'updated_at',
    ];

    public function ledger()
    {
        return $this->belongsTo(Ledger::class, 'ledger_id')->select('id', 'name');
    }

    public function conditions()
    {
        return $this->hasMany(BankRuleCondition::class);
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
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->select('id', 'name');
    }
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by')->select('id', 'name');
    }
}
