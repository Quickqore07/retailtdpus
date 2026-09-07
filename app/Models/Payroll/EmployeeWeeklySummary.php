<?php

namespace App\Models\Payroll;

use App\Models\Employee;
use App\Models\Settings\Company;
use App\Models\Settings\EmployeeRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Support\AuthorizedCompanies;
use App\Support\AuthorizedWorkgroup;
class EmployeeWeeklySummary extends Model
{
    use HasFactory, AuthorizedCompanies, AuthorizedWorkgroup;

    protected $table = 'employee_weekly_summary';

    protected $fillable = [
        'employee_id',
        'role_id',
        'company_id',
        'eow',
        'pay_type',
        'total_hours',
        'regular_hours',
        'overtime_hours',
        'tips',
        'mileage_excess',
        'incentive',
        'bonus',
        'tips_due',
        'mileage_due',
        'payroll_methods',
        'check_methods',
        'instant_methods',
        'gross_pay',
        'total_earnings',
        'hr_pay',
        'rate',
        'rate_type',
        'slab_first_hours',
        'slab_rest_rate',
        'min_wage_hourly',
        'min_wage_weekly',
        'min_wage_due',
        'mwa_amount',
        'calculated_check_amount',
        'calculated_instant_amount',
        'calculated_payroll_amount',
        'is_reviewed',
        'reviewed_at',
        'reviewed_by',
        'original_calculated_data',
    ];

    protected $casts = [
        'total_hours' => 'decimal:2',
        'regular_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'tips' => 'decimal:2',
        'mileage_excess' => 'decimal:2',
        'incentive' => 'decimal:2',
        'bonus' => 'decimal:2',
        'tips_due' => 'decimal:2',
        'mileage_due' => 'decimal:2',
        'payroll_methods' => 'decimal:2',
        'check_methods' => 'decimal:2',
        'instant_methods' => 'decimal:2',
        'gross_pay' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'hr_pay' => 'decimal:2',
        'rate' => 'decimal:2',
        'slab_first_hours' => 'decimal:2',
        'slab_rest_rate' => 'decimal:2',
        'min_wage_hourly' => 'decimal:2',
        'min_wage_weekly' => 'decimal:2',
        'min_wage_due' => 'decimal:2',
        'mwa_amount' => 'decimal:2',
        'calculated_check_amount' => 'decimal:2',
        'calculated_instant_amount' => 'decimal:2',
        'calculated_payroll_amount' => 'decimal:2',
        'is_reviewed' => 'boolean',
        'reviewed_at' => 'datetime',
        'original_calculated_data' => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class)->select('id', 'employee_id', 'ssn','pos_name','employee_type');
    }

    public function role()
    {
        return $this->belongsTo(EmployeeRoles::class, 'role_id')->select('id', 'name', 'code', 'tipped');
    }

    public function company()
    {
        return $this->belongsTo(Company::class)->selectRaw('id, CONCAT(store_number, " - ", name) as name, store_number,state_id');
    }
}
