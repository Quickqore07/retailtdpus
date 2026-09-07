<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Onboarding\EmployeeHoursRequest;
use App\Models\Payroll\EmployeeHours;
use App\Models\Payroll\EmployeeRates;
use App\Models\Settings\Company;
use App\Models\Settings\EmployeeRoles;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EmployeeHoursRequestSubmissionService
{
    public function __construct(
        private EmployeeService $employeeService
    ) {}

    /**
     * Create a pending employee hours request (optionally replacing prior pending rows for the same day/employee/company/role).
     *
     * @return array<string, mixed> Payload for to_json()
     */
    public function submit(Request $request, bool $rejectPendingDuplicates = true): array
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'date' => 'required|date',
                'employee_id' => 'required',
                'company_id' => 'required|exists:company,id',
                'role_id' => 'required|exists:employee_roles,id',
                'pay_type' => 'required|in:HR,WK',
                'total_hours' => 'required|numeric|min:0',
                'pay_rate' => 'required|numeric|min:0',
                'tips' => 'nullable|numeric|min:0',
                'mileage_excess' => 'nullable|numeric|min:0',
                'incentive' => 'nullable|numeric|min:0',
                'bonus' => 'nullable|numeric|min:0',
                'ssn' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return [
                    'saved' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                    '_http_status' => 422,
                ];
            }

            $employee = Employee::find($request->employee_id);
            if (! $employee) {
                return [
                    'saved' => false,
                    'message' => 'Employee not found',
                    '_http_status' => 404,
                ];
            }

            $dateStr = Carbon::parse($request->date)->format('Y-m-d');

            $existingEmployeeHours = EmployeeHours::authorizedCompanies('company_id')
                ->where('date', $dateStr)
                ->where('employee_id', (int) $request->employee_id)
                ->where('company_id', (int) $request->company_id)
                ->where('role_id', (int) $request->role_id)
                ->first();

            if ($rejectPendingDuplicates) {
                EmployeeHoursRequest::authorizedCompanies('company_id')
                    ->where('status', 'pending')
                    ->where('date', $dateStr)
                    ->where('employee_id', (int) $request->employee_id)
                    ->where('company_id', (int) $request->company_id)
                    ->where('role_id', (int) $request->role_id)
                    ->update([
                        'status' => 'rejected',
                        'rejected_by' => Auth::id(),
                        'rejected_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            $input = $request->all();
            $input['employee_id'] = $request->employee_id;

            $employee_rates = EmployeeRates::where('employee_id', $request->employee_id)->get();

            if (collect($employee_rates)->where('role_id', $request->role_id)->where('company_id', $request->company_id)->where('pay_type', $request->pay_type)->isEmpty()) {
                $this->employeeService->createEmployeeRates($input);
            }

            $tipsDue = $request->get('tips_due') ?? 0;
            $mileageDue = $request->get('mileage_due') ?? 0;

            $hoursRequest = EmployeeHoursRequest::create([
                'employee_hours_id' => $existingEmployeeHours?->id,
                'employee_id' => $request->employee_id,
                'company_id' => $request->company_id,
                'role_id' => $request->role_id,
                'date' => $dateStr,
                'employee_name' => $employee->pos_name ?? $employee->employee_id ?? '',
                'dev_id' => $request->get('dev_id'),
                'ssn' => $request->ssn,
                'pay_id' => $request->get('pay_id'),
                'pay_type' => $request->pay_type,
                'total_hours' => $request->total_hours,
                'tips' => $request->get('tips') ?? 0,
                'mileage_excess' => $request->get('mileage_excess') ?? 0,
                'incentive' => $request->get('incentive') ?? 0,
                'bonus' => $request->get('bonus') ?? 0,
                'home_store' => $request->get('home_store'),
                'pay_rate' => $request->pay_rate,
                'tips_due' => $tipsDue,
                'mileage_due' => $mileageDue,
                'status' => 'pending',
            ]);

            $companiesCache = Company::select('id', 'name')->get()->keyBy('id');
            $rolesCache = EmployeeRoles::select('id', 'name', 'code')->get()->keyBy('id');
            $emailInput = array_merge($input, ['tips_due' => $tipsDue, 'mileage_due' => $mileageDue]);
            $emailRequest = $this->formatHoursRequestForEmail($emailInput, $companiesCache, $rolesCache);

            EmployeeHoursRequestNotificationService::notifyNewRequest(
                $employee,
                $emailRequest,
                (int) $request->company_id,
                Auth::user()?->name ?? 'System'
            );

            DB::commit();

            return [
                'id' => $hoursRequest->id,
                'employeeHoursRequest' => $hoursRequest,
                'saved' => true,
                'message' => 'Employee hours request submitted for approval',
                'after_save_redirect' => '/payroll/employee-hours-request/'.$hoursRequest->id,
                '_http_status' => 200,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);

            return [
                'saved' => false,
                'message' => 'Employee hours creation failed',
                'error' => $e->getMessage(),
                '_http_status' => 500,
            ];
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\Settings\Company>|\Illuminate\Support\Collection<string, \App\Models\Settings\Company>  $companiesCache
     * @param  \Illuminate\Support\Collection<int, \App\Models\Settings\EmployeeRoles>|\Illuminate\Support\Collection<string, \App\Models\Settings\EmployeeRoles>  $rolesCache
     * @return array<string, mixed>
     */
    private function formatHoursRequestForEmail(array $data, $companiesCache, $rolesCache): array
    {
        $company = $companiesCache->get($data['company_id'] ?? null);
        $role = $rolesCache->get($data['role_id'] ?? null);

        return [
            'company' => $company?->name ?? '-',
            'role' => $role ? ($role->code.' - '.$role->name) : '-',
            'date' => $data['date'] ?? '-',
            'total_hours' => $data['total_hours'] ?? '-',
            'pay_rate' => '$'.number_format((float) ($data['pay_rate'] ?? 0), 2),
            'tips' => '$'.number_format((float) ($data['tips'] ?? 0), 2),
            'tips_due' => '$'.number_format((float) ($data['tips_due'] ?? 0), 2),
            'mileage_excess' => '$'.number_format((float) ($data['mileage_excess'] ?? 0), 2),
            'mileage_due' => '$'.number_format((float) ($data['mileage_due'] ?? 0), 2),
        ];
    }
}
