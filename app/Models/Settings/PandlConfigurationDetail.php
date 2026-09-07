<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PandlConfigurationDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'pandl_id',
        'ledgers',
        'type',
        'cogs_type',
        'label',
    ];

    public function pandlConfiguration()
    {
        return $this->belongsTo(PandlConfiguration::class, 'pandl_id');
    }

    public function getLedgersArrayAttribute()
    {
        return $this->ledgers ? explode(',', $this->ledgers) : [];
    }

    public function setLedgersArrayAttribute($value)
    {
        $this->attributes['ledgers'] = is_array($value) ? implode(',', array_filter($value)) : $value;
    }
}
