<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingsItem extends Model
{
    use HasFactory;

    protected $table = 'settings_items';

    protected $fillable = [
        'report_type',
        'difference_condition',
        'difference_value_1',
        'difference_value_2',
        'difference_color',
    ];
}
