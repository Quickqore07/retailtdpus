<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAlias extends Model
{
    protected $table = 'employee_aliases';

    protected $fillable = [
        'employee_id',
        'alias_employee_id',
        'alias_name',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
