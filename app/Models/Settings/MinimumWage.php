<?php

namespace App\Models\Settings;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\Filterable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Traits\LogsActivity;

class MinimumWage extends Model
{
    use HasFactory, Filterable, LogsActivity;

    protected $table = 'minimum_wages';

    protected $fillable = [
        'state_id',
        'county_id',
    ];

    protected $activityFields = [
        ['field' => 'state_id', 'label' => 'State', 'table' => 'state', 'table_field' => 'name', 'type' => 'lookup'],
        ['field' => 'county_id', 'label' => 'County', 'table' => 'county', 'table_field' => 'name', 'type' => 'lookup'],
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
        'state_id',
        'county_id',
        'created_at',
        'updated_at',
    ];

    protected $searchable = [
        'state_id',
        'county_id',
    ];

    protected $allowedFilters = [
        'state_id',
        'county_id',
        'created_at',
        'updated_at',
    ];

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function county()
    {
        return $this->belongsTo(County::class);
    }

    public function items()
    {
        return $this->hasMany(MinimumWageItem::class)->orderByDesc('effective_date');
    }

    /**
     * Latest minimum wage item per state as of the given date, keyed by state_id.
     */
    public static function latestItemsKeyedByState($date): Collection
    {
        $date = Carbon::parse($date)->toDateString();

        $latestPerState = DB::table('minimum_wage_items as mwi')
            ->join('minimum_wages as mw', 'mw.id', '=', 'mwi.minimum_wage_id')
            ->where('mwi.effective_date', '<=', $date)
            ->select('mw.state_id', DB::raw('MAX(mwi.effective_date) as max_effective_date'))
            ->groupBy('mw.state_id');

        return MinimumWageItem::query()
            ->select('minimum_wage_items.*', 'minimum_wages.state_id')
            ->join('minimum_wages', 'minimum_wages.id', '=', 'minimum_wage_items.minimum_wage_id')
            ->joinSub($latestPerState, 'latest', function ($join) {
                $join->on('minimum_wages.state_id', '=', 'latest.state_id')
                    ->on('minimum_wage_items.effective_date', '=', 'latest.max_effective_date');
            })
            ->get()
            ->keyBy('state_id');
    }

    /**
     * Latest minimum wage item for a single state as of the given date.
     */
    public static function latestItemForState(int $stateId, $date): ?MinimumWageItem
    {
        return static::latestItemsKeyedByState($date)->get($stateId);
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
