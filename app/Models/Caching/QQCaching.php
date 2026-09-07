<?php

namespace App\Models\Caching;

use Illuminate\Database\Eloquent\Model;

class QQCaching extends Model
{
    protected $table = 'qq_caching';
    protected $fillable = ['key', 'data'];
    protected $casts = [
        'data' => 'array',
    ];

    public static function getCache($key)
    {
        return self::where('key', $key)->first();
    }
    public static function setCache($key, $data)
    {
        return self::updateOrCreate(['key' => $key], ['data' => $data]);
    }
}