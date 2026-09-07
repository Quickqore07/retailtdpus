<?php

namespace App\Models\AP;

use App\Models\Traits\LogsActivity;
use App\Models\User;
use App\Support\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ExpenseType extends Model
{
    use HasFactory, Filterable, LogsActivity;

    public const ELECTRIC_GAS = 'Electric / Gas';

    protected $table = 'ap_expense_types';

    protected $fillable = [
        'name',
        'amount_label',
        'other_amount_label',
        'show_other_amount',
        'active',
        'created_by',
        'updated_by',
    ];

    protected $activityFields = [
        ['field' => 'name', 'label' => 'Name', 'type' => 'string'],
        ['field' => 'amount_label', 'label' => 'Amount Label', 'type' => 'number'],
        ['field' => 'other_amount_label', 'label' => 'Other Amount Label', 'type' => 'number'],
        ['field' => 'show_other_amount', 'label' => 'Show Other Amount', 'type' => 'number'],
        ['field' => 'active', 'label' => 'Active', 'type' => 'boolean'],
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
        'show_other_amount' => 'boolean',
        'active' => 'boolean',
    ];

    protected $sortable = [
        'name',
        'amount_label',
        'other_amount_label',
        'show_other_amount',
        'active',
        'created_at',
        'updated_at',
    ];

    protected $searchable = [
        'name',
        'amount_label',
        'other_amount_label',
    ];

    protected $allowedFilters = [
        'name',
        'amount_label',
        'other_amount_label',
        'show_other_amount',
        'active',
        'created_at',
        'updated_at',
    ];

    protected $searchableColumns = [
        'name',
        'amount_label',
        'other_amount_label',
    ];

    public function isAmountOptional(): bool
    {
        return $this->name === self::ELECTRIC_GAS;
    }

    public function purchaseInvoices()
    {
        return $this->hasMany(PurchaseInvoice::class, 'expense_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->select('id', 'name');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by')->select('id', 'name');
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

    public static function generateUniqueCode(string $name): string
    {
        $base = Str::slug($name, '_');

        if ($base === '') {
            $base = 'expense';
        }

        if (strlen($base) > 45) {
            $base = substr($base, 0, 45);
        }

        $code = $base;
        $counter = 1;

        while (static::where('code', $code)->exists()) {
            $code = $base . '_' . $counter;
            $counter++;
        }

        return $code;
    }
}
