<?php

namespace App\Models\Settings;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\LogsActivity;

class PandlConfiguration extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'label',
        'created_by',
        'updated_by',
    ];

    protected $activityFields = [
        ['field' => 'label', 'label' => 'Label', 'type' => 'string'],
        ['field' => 'count', 'label' => 'Count', 'type' => 'number'],
        ['field' => 'date_from', 'label' => 'Date From', 'type' => 'date'],
        ['field' => 'date_to', 'label' => 'Date To', 'type' => 'date'],
        ['field' => 'dates', 'label' => 'Dates', 'type' => 'array'],
        ['field' => 'filename', 'label' => 'Filename', 'type' => 'string'],
        ['field' => 'source', 'label' => 'Source', 'type' => 'string'],
        ['field' => 'imported_at', 'label' => 'Imported At', 'type' => 'string'],
        ['field' => 'ids', 'label' => 'Record IDs', 'type' => 'array'],
    ];

    public function details()
    {
        return $this->hasMany(PandlConfigurationDetail::class, 'pandl_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeFilter($query)
    {
        $search = request('search');
        $perPage = request('perPage', 10);
        $orderBy = request('orderBy', 'id');
        $order = request('order', 'desc');

        if ($search) {
            $query->where('label', 'like', "%{$search}%");
        }

        return $query->orderBy($orderBy, $order)->paginate($perPage);
    }
}
