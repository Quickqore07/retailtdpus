<?php

namespace App\Models\AR;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArEmailTemplate extends Model
{
    use HasFactory;

    protected $table = 'ar_email_templates';

    protected $fillable = [
        'name',
        'template_type',
        'is_default',
        'subject',
        'body',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
