<?php

namespace App\Models\AP;

use App\Models\Traits\LogsActivity;
use App\Models\User;
use App\Support\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Vendor extends Model
{
    use HasFactory, Filterable, LogsActivity;

    protected $table = 'ap_vendors';

    protected $fillable = [
        'name',
        'code',
        'email',
        'mobile',
        'fax',
        'credit_days',
        'address_line_1',
        'address_line_2',
        'address_line_3',
        'city',
        'state',
        'country',
        'created_by',
        'updated_by',
    ];

    protected $activityFields = [
        ['field' => 'name', 'label' => 'Name', 'type' => 'string'],
        ['field' => 'code', 'label' => 'Code', 'type' => 'string'],
        ['field' => 'email', 'label' => 'Email', 'type' => 'string'],
        ['field' => 'mobile', 'label' => 'Mobile', 'type' => 'string'],
        ['field' => 'fax', 'label' => 'Fax', 'type' => 'string'],
        ['field' => 'credit_days', 'label' => 'Credit Days', 'type' => 'number'],
        ['field' => 'address_line_1', 'label' => 'Address Line 1', 'type' => 'string'],
        ['field' => 'address_line_2', 'label' => 'Address Line 2', 'type' => 'string'],
        ['field' => 'address_line_3', 'label' => 'Address Line 3', 'type' => 'string'],
        ['field' => 'city', 'label' => 'City', 'type' => 'string'],
        ['field' => 'state', 'label' => 'State', 'type' => 'string'],
        ['field' => 'country', 'label' => 'Country', 'type' => 'number'],
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
        'credit_days' => 'integer',
    ];

    protected $sortable = [
        'name',
        'code',
        'email',
        'mobile',
        'fax',
        'credit_days',
        'city',
        'state',
        'country',
        'created_at',
        'updated_at',
    ];

    protected $searchable = [
        'name',
        'code',
        'email',
        'mobile',
        'fax',
        'city',
        'state',
        'country',
    ];

    protected $allowedFilters = [
        'name',
        'code',
        'email',
        'mobile',
        'fax',
        'city',
        'state',
        'country',
        'created_at',
        'updated_at',
    ];
    protected $searchableColumns = [
        'name',
        'code',
        'email',
        'mobile',
        'fax',
        'city',
        'state',
        'country',
    ];

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
}
