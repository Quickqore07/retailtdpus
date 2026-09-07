<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Onboarding\EmployeeRateRequestController as OnboardingEmployeeRateRequestController;
use App\Models\Employee;
use App\Models\Onboarding\EmployeeRateRequest;
use App\Models\Settings\Company;
use App\Models\Settings\EmployeeRoles;
use App\Services\EmployeeRateRequestNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeNewRateRequestController extends OnboardingEmployeeRateRequestController
{
    public function index(Request $request)
    {
        $this->authorize('access', 'employee-new-rate-request.index');

        $employeeRateRequests = EmployeeRateRequest::with('employee', 'role', 'company', 'hrApprovedBy', 'doApprovedBy', 'adminApprovedBy')
            ->whereHas('employee', function ($q) {
                $q->where('employee_type', 'Completed');
            })
            ->authorizedCompanies('company_id')
            ->filter();

        return to_json([
            'collection' => $employeeRateRequests,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'employee-new-rate-request.create');

        $rules = [
            'employee_id' => 'required',
            'company_id' => 'required',
            'role_id' => 'required',
            'pay_type' => 'required|in:HR,WK',
            'rate_type' => 'required|in:Payroll Regular,Payroll Slab,Payroll 1099,1099 Regular,1099 Slab,1099 1099',
            'payroll_type' => 'nullable|in:Direct Deposit,Print on site',
            'rate' => 'required|numeric|min:0',
            'slab_first_hours' => 'nullable|numeric|min:0',
            'slab_rest_rate' => 'nullable|numeric|min:0',
            'payroll_hours' => 'nullable|numeric|min:0',
            'payroll_hours_type' => 'nullable|in:fixed,percentage',
            'check_payment_type' => 'nullable|in:fixed,percentage',
            'check_payment_amount' => 'nullable|numeric|min:0',
            'effective_date' => 'required|date',
            'till_date' => 'nullable|date|after_or_equal:effective_date',
            'notes' => 'nullable|string',
            'employee_rate_id' => 'nullable',
        ];
        if ($request->has('employee_profile')) {
            $rules = array_merge($rules, $this->employeeProfileValidationRules());
        }
        $validated = $request->validate($rules);

        if ($request->has('employee_profile') && is_array($request->input('employee_profile'))) {
            $this->syncEmployeeFromRateRequestProfile((int) $validated['employee_id'], $request->input('employee_profile'));
        }

        $rateTypes1099 = ['Payroll 1099', '1099 Regular', '1099 Slab', '1099 1099'];
        $noPictureId = $request->boolean('no_picture_id');
        if (in_array($validated['rate_type'], $rateTypes1099, true) && ! $noPictureId) {
            $employee = Employee::find($validated['employee_id']);
            if (! $employee || empty($employee->profile_picture)) {
                return to_json([
                    'message' => 'Profile picture is required for the employee when rate type is 1099.',
                    'errors' => ['profile_picture' => ['Profile picture is required when rate type is 1099.']],
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            // Reject any older pending request for same employee/company/role before creating a fresh one.
            EmployeeRateRequest::query()
                ->where('employee_id', $validated['employee_id'])
                ->where('company_id', $validated['company_id'])
                ->where('role_id', $validated['role_id'])
                ->where('status', 'pending')
                ->update([
                    'status' => 'rejected',
                    'rejected_by' => Auth::id(),
                    'rejected_at' => now(),
                    'rejection_reason' => 'Auto rejected due to newer request for same employee, company, and role.',
                    'hr_approved' => false,
                    'hr_approved_at' => null,
                    'hr_approved_by' => null,
                    'do_approved' => false,
                    'do_approved_at' => null,
                    'do_approved_by' => null,
                    'admin_approved' => false,
                    'admin_approved_at' => null,
                    'admin_approved_by' => null,
                ]);

            $validated['status'] = 'pending';
            $validated['payroll_type'] = $request->payroll_type ?? null;
            $validated['slab_first_hours'] = $request->slab_first_hours ?? 0;
            $validated['slab_rest_rate'] = $request->slab_rest_rate ?? 0;
            $validated['payroll_hours'] = $request->payroll_hours ?? 0;
            $validated['payroll_hours_type'] = $request->payroll_hours_type ?? 'hours';
            $validated['check_payment_type'] = $request->check_payment_type ?? 'fixed';
            $validated['check_payment_amount'] = $request->check_payment_amount ?? 0;
            $validated['effective_date'] = $request->effective_date;
            $validated['till_date'] = $request->till_date;

            $employeeRateRequest = EmployeeRateRequest::create($validated);
            $employeeRateRequest->load(['employee', 'role', 'company']);

            $employee = $employeeRateRequest->employee;
            if (! $employee) {
                $employee = Employee::find($validated['employee_id']);
            }
            $rolesCache = EmployeeRoles::select('id', 'name', 'code')->authorizedWorkgroup()->get()->keyBy('id');
            $companiesCache = Company::select('id', 'name')->get()->keyBy('id');
            $emailRequest = $this->formatRateRequestForEmail($validated, $rolesCache, $companiesCache);
            if ($employee) {
                $requestedBy = Auth::user()?->name ?? 'System';
                EmployeeRateRequestNotificationService::notifyNewRequests(
                    $employee,
                    [$validated],
                    [$emailRequest],
                    $requestedBy
                );
            }

            DB::commit();
            return to_json([
                'id' => $employeeRateRequest->id,
                'saved' => true,
                'message' => 'Employee rate request created successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'Failed to create employee rate request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $this->authorize('access', 'employee-new-rate-request.show');

        $employeeRateRequest = EmployeeRateRequest::with('employee', 'role', 'company', 'employeeRate', 'hrApprovedBy', 'doApprovedBy', 'adminApprovedBy')
            ->whereHas('employee', function ($q) {
                $q->where('employee_type', 'Completed');
            })
            ->authorizedCompanies('company_id')
            ->findOrFail($id);

        return to_json([
            'model' => $employeeRateRequest,
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'employee-new-rate-request.update');

        $employeeRateRequest = EmployeeRateRequest::with('employee', 'role', 'company')
            ->whereHas('employee', function ($q) {
                $q->where('employee_type', 'Completed');
            })
            ->authorizedCompanies('company_id')
            ->findOrFail($id);

        if ($employeeRateRequest->status !== 'pending') {
            return to_json([
                'form' => null,
                'message' => 'Cannot edit approved or rejected rate request',
            ], 403);
        }

        return to_json([
            'form' => $employeeRateRequest,
        ]);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('access', 'employee-new-rate-request.update');

        $employeeRateRequest = EmployeeRateRequest::with('employee', 'role', 'company')
            ->whereHas('employee', function ($q) {
                $q->where('employee_type', 'Completed');
            })
            ->authorizedCompanies('company_id')
            ->findOrFail($id);

        if ($employeeRateRequest->status !== 'pending') {
            return to_json([
                'model' => null,
                'message' => 'Cannot edit approved or rejected rate request',
            ], 403);
        }

        return parent::update($request, $id);
    }
}
