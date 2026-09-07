<?php

namespace App\Models\Upload;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Filterable;
use Illuminate\Support\Facades\Auth;
use App\Models\Traits\LogsActivity;

class UploadFolder extends Model
{
    use HasFactory, Filterable, LogsActivity;

    protected $table = 'upload_folders';

    protected $fillable = [
        'name',
        'hide',
        'is_invoice',
        'is_sales_invoice',
        'is_check',
    ];

    protected $activityFields = [
        ['field' => 'name', 'label' => 'Name', 'type' => 'string'],
        ['field' => 'hide', 'label' => 'Hide', 'type' => 'string'],
        ['field' => 'is_invoice', 'label' => 'Is Invoice', 'type' => 'boolean'],
        ['field' => 'is_sales_invoice', 'label' => 'Is Sales Invoice', 'type' => 'boolean'],
        ['field' => 'is_check', 'label' => 'Is Check', 'type' => 'boolean'],
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
        'created_at',
        'updated_at',
    ];

    protected $searchableColumns = [
        'name',
    ];

    protected $searchable = [
        'name',
    ];

    protected $allowedFilters = [
        'name',
        'created_at',
        'updated_at',
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
