<?php

namespace App\Models\Payroll;

use App\Models\Settings\Company;
use App\Models\User;
use App\Support\AuthorizedCompanies;
use Illuminate\Database\Eloquent\Model;

class EmployeeHoursMissingDeleted extends Model
{
    use AuthorizedCompanies;

    protected $table = 'employee_hours_missing_deleted';

    protected $fillable = [
        'date',
        'company_id',
        'deleted_by',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class)->selectRaw(
            'id, CONCAT(store_number, " - ", name) as name, store_number, workgroup_id'
        )->with('workgroup');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
