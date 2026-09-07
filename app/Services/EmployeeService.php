<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payroll\EmployeeRates;
use App\Models\Settings\Company;
use Illuminate\Support\Facades\Auth;

class EmployeeService
{
    function createEmployee($data)
    {
        try {
            $workgroup = Company::select('workgroup_id')->find($data['company_id']);
            $payload = [
                'employee_id' => $data['employee_id'],
                'pos_name' => $data['employee_name'],
                'active' => true,
                'employee_type' => $data['employee_type'] ?? 'New',
                'ssn' => $data['ssn'] ?? null,
                'hire_date' => $data['date'],
                'workgroup_id' => $workgroup->workgroup_id
            ];
            $employee = Employee::create($payload);
            return $employee;
        } catch (\Exception $e) {
            throw new \Exception('Employee creation failed: ' . $e->getMessage());
        }
    }
    function createEmployeeRates($data)
    {
        try {
            $payload = [
                'employee_id' => $data['employee_id'],
                'role_id' => $data['role_id'],
                'pay_type' => $data['pay_type'],
                'rate' => $data['pay_rate'],
                'rate_type' => 'Payroll Regular',
                'payroll_type' => 'Direct Deposit',
                'effective_date' => $data['date'],
                'till_date' => null,
                'company_id' => $data['company_id'],
                'payroll_hours_type' => 'fixed',
                'payroll_hours' =>0,
                'check_payment_type' => 'fixed',
                'check_payment_amount' => 0,
                'company_id' => $data['company_id'],
                'till_date' => null,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ];
            $employee_rates = EmployeeRates::create($payload);
            return $employee_rates;
        } catch (\Exception $e) {
            throw new \Exception('Employee rates creation failed: ' . $e->getMessage());
        }
    }
}