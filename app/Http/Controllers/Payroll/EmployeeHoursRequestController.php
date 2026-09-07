<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Onboarding\EmployeeHoursRequest;
use App\Models\Payroll\EmployeeHours;
use App\Models\Payroll\EmployeeRates;
use App\Services\EmployeeHoursRequestSubmissionService;
use App\Services\WeeklySummaryRecalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EmployeeHoursRequestController extends Controller
{
    public function create()
    {
        $this->authorize('access', 'employee-hours-request.create');

        $item = [
            'date' => now()->format('Y-m-d'),
            'employee_id' => null,
            'company_id' => null,
            'dev_id' => 0,
            'ssn' => '',
            'pay_id' => 0,
            'pay_type' => 'HR',
            'total_hours' => 0,
            'tips' => 0,
            'mileage_excess' => 0,
            'incentive' => 0,
            'bonus' => 0,
            'home_store' => 0,
            'role_id' => null,
            'pay_rate' => 0,
            'tips_due' => 0,
            'mileage_due' => 0,
        ];

        return to_json([
            'form' => $item,
        ]);
    }

    /**
     * Roles that have an employee_rates row for this employee and company (for hours request role picker).
     */
    public function rolesForSelection(Request $request)
    {
        $this->authorize('access', 'employee-hours-request.create');

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|integer',
            'company_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return to_json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $rates = EmployeeRates::authorizedCompanies('company_id')
            ->where('employee_id', (int) $request->employee_id)
            ->where('company_id', (int) $request->company_id)
            ->with('role')
            ->get()
            ->unique('role_id');

        $roles = $rates
            ->map(function (EmployeeRates $rate) {
                $role = $rate->role;
                if (! $role) {
                    return null;
                }

                return [
                    'id' => $role->id,
                    'name' => ($role->code ? $role->code.' - ' : '').($role->name ?? ''),
                    'code' => $role->code,
                ];
            })
            ->filter()
            ->sortBy(fn (array $r) => $r['name'] ?? '')
            ->values()
            ->all();

        return to_json([
            'roles' => $roles,
        ]);
    }

    /**
     * Return existing payroll hours and/or pending request for the selected date, employee, company, and role.
     */
    public function lookup(Request $request)
    {
        $this->authorize('access', 'employee-hours-request.create');

        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'employee_id' => 'required|integer',
            'company_id' => 'required|integer',
            'role_id' => 'required|integer',
            'pay_type' => 'nullable|in:HR,WK',
        ]);

        if ($validator->fails()) {
            return to_json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $dateStr = Carbon::parse($request->date)->format('Y-m-d');

        $employeeHours = EmployeeHours::with(['employee', 'company', 'role'])
            ->authorizedCompanies('company_id')
            ->where('date', $dateStr)
            ->where('employee_id', (int) $request->employee_id)
            ->where('company_id', (int) $request->company_id)
            ->where('role_id', (int) $request->role_id)
            ->first();

        $pendingRequest = EmployeeHoursRequest::with(['employee', 'company', 'role'])
            ->authorizedCompanies('company_id')
            ->where('status', 'pending')
            ->where('date', $dateStr)
            ->where('employee_id', (int) $request->employee_id)
            ->where('company_id', (int) $request->company_id)
            ->where('role_id', (int) $request->role_id)
            ->orderByDesc('id')
            ->first();

        $payType = $request->get('pay_type', 'HR');
        $suggestedPayRate = null;
        $rateRow = EmployeeRates::authorizedCompanies('company_id')
            ->where('employee_id', (int) $request->employee_id)
            ->where('company_id', (int) $request->company_id)
            ->where('role_id', (int) $request->role_id)
            ->where('pay_type', $payType)
            ->first();
        if ($rateRow !== null) {
            $suggestedPayRate = $rateRow->rate !== null ? (float) $rateRow->rate : null;
        }

        return to_json([
            'employee_hours' => $employeeHours,
            'pending_request' => $pendingRequest,
            'suggested_pay_rate' => $suggestedPayRate,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'employee-hours-request.create');

        $payload = app(EmployeeHoursRequestSubmissionService::class)->submit($request, true);
        $status = $payload['_http_status'] ?? 200;
        unset($payload['_http_status']);

        return to_json($payload, $status);
    }

    public function index(Request $request)
    {
        $this->authorize('access', 'employee-hours-request.index');

        $employeeHoursRequests = EmployeeHoursRequest::with('employee', 'role', 'company', 'hrApprovedBy', 'doApprovedBy', 'adminApprovedBy')
            ->authorizedCompanies('company_id')
            ->filter();

        return to_json([
            'collection' => $employeeHoursRequests,
        ]);
    }

    public function show($id)
    {
        $this->authorize('access', 'employee-hours-request.show');

        $employeeHoursRequest = EmployeeHoursRequest::with('employee', 'role', 'company', 'employeeHour', 'hrApprovedBy', 'doApprovedBy', 'adminApprovedBy')
            ->authorizedCompanies('company_id')
            ->findOrFail($id);

        if (!$employeeHoursRequest) {
            return to_json([
                'model' => null,
                'message' => 'Employee hours request not found',
            ], 404);
        }

        return to_json([
            'model' => $employeeHoursRequest,
        ]);
    }

    public function approve(Request $request, $id)
    {
        $this->authorize('access', 'employee-hours-request.update');

        $hoursRequest = EmployeeHoursRequest::with('employee', 'role', 'company')
            ->authorizedCompanies('company_id')
            ->findOrFail($id);

        if (!$hoursRequest) {
            return to_json([
                'model' => null,
                'message' => 'Employee hours request not found',
            ], 404);
        }

        DB::beginTransaction();
        try {
            $isNewHoursWithoutRow = $hoursRequest->employee_hours_id === null;

            $userRole = Auth::user()->role->name ?? null;
            $approvalFields = [];

            if ($userRole === 'HR') {
                $approvalFields = [
                    'hr_approved_by' => Auth::id(),
                    'hr_approved_at' => now(),
                    'hr_approved' => true,
                    'status' => 'approved',
                ];
            } elseif ($userRole === 'admin' || $userRole === 'superadmin') {
                $approvalFields = [
                    'admin_approved_by' => Auth::id(),
                    'admin_approved_at' => now(),
                    'admin_approved' => true,
                    'status' => 'approved',
                ];
            } else {
                $approvalFields = [
                    'do_approved_by' => Auth::id(),
                    'do_approved_at' => now(),
                    'do_approved' => true,
                    'status' => 'approved',
                ];
            }

            $hoursRequest->update(array_merge($approvalFields, ['updated_at' => now()]));
            $hoursRequest->refresh();

            if ($hoursRequest->status === 'approved' && $isNewHoursWithoutRow) {
                $employeeHours = EmployeeHours::create([
                    'date' => $hoursRequest->date,
                    'employee_id' => $hoursRequest->employee_id,
                    'employee_name' => $hoursRequest->employee_name ?? $hoursRequest->employee?->pos_name ?? '',
                    'company_id' => $hoursRequest->company_id,
                    'dev_id' => $hoursRequest->dev_id,
                    'ssn' => $hoursRequest->ssn,
                    'pay_id' => $hoursRequest->pay_id,
                    'pay_type' => $hoursRequest->pay_type,
                    'total_hours' => $hoursRequest->total_hours,
                    'tips' => $hoursRequest->tips ?? 0,
                    'mileage_excess' => $hoursRequest->mileage_excess ?? 0,
                    'incentive' => $hoursRequest->incentive ?? 0,
                    'bonus' => $hoursRequest->bonus ?? 0,
                    'home_store' => $hoursRequest->home_store,
                    'role_id' => $hoursRequest->role_id,
                    'pay_rate' => $hoursRequest->pay_rate,
                    'tips_due' => $hoursRequest->tips_due ?? 0,
                    'mileage_due' => $hoursRequest->mileage_due ?? 0,
                ]);
                $hoursRequest->update(['employee_hours_id' => $employeeHours->id]);

                WeeklySummaryRecalculationService::recalculateForEmployee([
                    'employee_id' => $hoursRequest->employee_id,
                    'start_date' => $hoursRequest->date,
                    'end_date' => $hoursRequest->date,
                ]);
            } elseif ($hoursRequest->status === 'approved' && $hoursRequest->employee_hours_id) {
                $employeeHours = EmployeeHours::authorizedCompanies('company_id')->find($hoursRequest->employee_hours_id);
                if ($employeeHours) {
                    $employeeHours->update([
                        'company_id' => $hoursRequest->company_id,
                        'role_id' => $hoursRequest->role_id,
                        'total_hours' => $hoursRequest->total_hours,
                        'pay_rate' => $hoursRequest->pay_rate,
                        'tips' => $hoursRequest->tips,
                        'tips_due' => $hoursRequest->tips_due ?? 0,
                        'mileage_excess' => $hoursRequest->mileage_excess,
                        'mileage_due' => $hoursRequest->mileage_due ?? 0,
                        'updated_at' => now(),
                    ]);

                    WeeklySummaryRecalculationService::recalculateForEmployee([
                        'employee_id' => $hoursRequest->employee_id,
                        'start_date' => $employeeHours->date,
                        'end_date' => $employeeHours->date,
                    ]);
                }
            }

            DB::commit();

            return to_json([
                'success' => true,
                'message' => 'Employee hours request approved successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'success' => false,
                'message' => 'Failed to approve employee hours request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $this->authorize('access', 'employee-hours-request.delete');

        try {
            $hoursRequest = EmployeeHoursRequest::authorizedCompanies('company_id')->findOrFail($id);
            if (!$hoursRequest) {
                return to_json([
                    'model' => null,
                    'message' => 'Employee hours request not found',
                ], 404);
            }
            $hoursRequest->delete();

            return to_json([
                'deleted' => true,
                'message' => 'Employee hours request deleted successfully',
            ]);
        } catch (\Exception $e) {
            return to_json([
                'deleted' => false,
                'message' => 'Failed to delete employee hours request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
