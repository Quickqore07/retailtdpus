<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payroll\EmployeeRates;
use App\Models\Onboarding\EmployeeRateRequest;
use App\Models\Settings\Company;
use App\Models\Settings\EmployeeRoles;
use App\Models\User;
use App\Services\EmployeeHiredNotificationService;
use App\Services\EmployeeRateRequestNotificationService;
use App\Services\MailService;
use App\Services\WeeklySummaryRecalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeRateRequestController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('access', 'employee-rate-request.index');

        
        $employeeRateRequests = EmployeeRateRequest::
            with('employee.aliases','role','company','hrApprovedBy','doApprovedBy','adminApprovedBy')
            ->where('status','pending')
            ->where(function ($q) {
                $q->whereDoesntHave('employee', function ($query) {
                      $query->where('rejected', true)->orWhere('onboarding_status', '=', 'pending');
                })->orWhereHas('employee', function ($query) {
                    $query->where('employee_type', 'Completed');
                });
            })
           
            ->authorizedCompanies('company_id')
            ->filter();

        return to_json([
            'collection' => $employeeRateRequests,
        ]);
    }
    public function show($id)
    {
        $this->authorize('access', 'employee-rate-request.show');
        $employeeRateRequest = EmployeeRateRequest::with('employee.aliases','role','company','employeeRate','hrApprovedBy','doApprovedBy','adminApprovedBy')->authorizedCompanies('company_id')->findOrFail($id);
        if(!$employeeRateRequest){
            return to_json([
                'model' => null,
                'message' => 'Employee rate request not found',
            ], 404);
        }
        return to_json([
            'model' => $employeeRateRequest,
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'employee-rate-request.create');
        
        return to_json([
            'form' => [
                'employee' => null,
                'company' => null,
                'role' => null,
                'pay_type' => 'Payroll',
                'rate_type' => 'Payroll Regular',
                'payroll_type' => 'Direct Deposit',
                'rate' => null,
                'slab_first_hours' => null,
                'slab_rest_rate' => null,
                'payroll_hours' => null,
                'payroll_hours_type' => 'hours',
                'check_payment_type' => 'fixed',
                'check_payment_amount' => 0,
                'effective_date' => date('Y-m-d'),
                'till_date' => null,
                'notes' => null,
            ]
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'employee-rate-request.update');
        
        $employeeRateRequest = EmployeeRateRequest::with('employee','role','company')->authorizedCompanies('company_id')->findOrFail($id);
        
        if(!$employeeRateRequest){
            return to_json([
                'form' => null,
                'message' => 'Employee rate request not found',
            ], 404);
        }

        // Only allow editing if status is pending
        if($employeeRateRequest->status !== 'pending'){
            return to_json([
                'form' => null,
                'message' => 'Cannot edit approved or rejected rate request',
            ], 403);
        }

        return to_json([
            'form' => $employeeRateRequest
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'employee-rate-request.create');
        
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
            if (!$employee || empty($employee->profile_picture)) {
                return to_json([
                    'message' => 'Profile picture is required for the employee when rate type is 1099.',
                    'errors' => ['profile_picture' => ['Profile picture is required when rate type is 1099.']],
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
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
            if (!$employee) {
                $employee = Employee::find($validated['employee_id']);
            }
            $rolesCache = EmployeeRoles::select('id', 'name', 'code')->authorizedWorkgroup()->get()->keyBy('id');
            $companiesCache = Company::select('id', 'name')->get()->keyBy('id');
            // $emailRequest = $this->formatRateRequestForEmail($validated, $rolesCache, $companiesCache);

            // if ($employee) {
            //     $hrUsers = User::whereHas('role', fn ($q) => $q->where('name', 'HR'))
            //         ->whereNotNull('email')
            //         ->where('email', '!=', '')
            //         ->authorizedWorkgroup()
            //         ->get();
            //     $requestedBy = Auth::user()?->name ?? 'System';

            //     foreach ($hrUsers as $hrUser) {
            //         try {
            //             $html = view('emails.employee-rate-requests', [
            //                 'employeeGroups' => [['employee' => $employee, 'requests' => [$emailRequest]]],
            //                 'requestedBy' => $requestedBy,
            //             ])->render();
            //             MailService::sendMail(
            //                 $hrUser->email,
            //                 'New Employee Rate Request - ' . ($employee->pos_name ?? $employee->employee_id ?? 'N/A'),
            //                 $html
            //             );
            //         } catch (\Throwable $e) {
            //             dd($e);
            //         }
            //     }
            // }

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

    public function update(Request $request, $id)
    {
        $this->authorize('access', 'employee-rate-request.update');
        
        $employeeRateRequest = EmployeeRateRequest::with('employee','role','company')->authorizedCompanies('company_id')->findOrFail($id);
        
        if(!$employeeRateRequest){
            return to_json([
                'model' => null,
                'message' => 'Employee rate request not found',
            ], 404);
        }

        // Only allow editing if status is pending
        if($employeeRateRequest->status !== 'pending'){
            return to_json([
                'model' => null,
                'message' => 'Cannot edit approved or rejected rate request',
            ], 403);
        }

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
            'ten99_rate' => 'nullable|numeric|min:0',
            'payroll_rate' => 'nullable|numeric|min:0',
        ];
        if ($request->has('employee_profile')) {
            $rules = array_merge($rules, $this->employeeProfileValidationRules());
        }
        $validated = $request->validate($rules);
        $validated['payroll_type'] = $request->payroll_type ?? null;

        if ($request->has('employee_profile') && is_array($request->input('employee_profile'))) {
            $this->syncEmployeeFromRateRequestProfile((int) $validated['employee_id'], $request->input('employee_profile'));
        }

        $rateTypes1099 = ['Payroll 1099', '1099 Regular', '1099 Slab', '1099 1099'];
        $noPictureId = $request->boolean('no_picture_id');
        if (in_array($validated['rate_type'], $rateTypes1099, true) && ! $noPictureId) {
            $employee = Employee::find($validated['employee_id']);
            if (!$employee || empty($employee->profile_picture)) {
                return to_json([
                    'message' => 'Profile picture is required for the employee when rate type is 1099.',
                    'errors' => ['profile_picture' => ['Profile picture is required when rate type is 1099.']],
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $rolesCache = EmployeeRoles::select('id', 'name', 'code')->authorizedWorkgroup()->get()->keyBy('id');
            $companiesCache = Company::select('id', 'name')->get()->keyBy('id');
            $oldData = $this->getRateRequestForComparison($employeeRateRequest);
            $newData = $this->buildRateRequestForComparison($validated, $rolesCache, $companiesCache);
            $comparison = $this->buildComparisonChanges($oldData, $newData);
            $employee = $employeeRateRequest->employee;
            DB::enableQueryLog();
            $employeeRateRequest->update($validated);
           
            // $employeeRateRequest->update($validated);

            if ($this->hasComparisonChanges($comparison)) {
                $employeeRateRequest->load('employee');
                $employee = $employeeRateRequest->employee ?? Employee::find($validated['employee_id']);
                if ($employee) {
                    $updatedBy = Auth::user()?->name ?? 'System';
                    EmployeeRateRequestNotificationService::notifyUpdatedRequests(
                        $employee,
                        [$validated],
                        [$comparison],
                        $updatedBy
                    );
                }
            }
            // if(!$employee->mail_sent && $employee->employee_type != 'Completed' && ($employeeRateRequest->rate_type == 'Payroll Regular' || $employeeRateRequest->rate_type == 'Payroll Slab' || $employeeRateRequest->rate_type == 'Payroll 1099')){
            //     $onboardingController = app(OnboardingController::class);
            //     $onboardingController->handleSendMailToEmployee($employee->id);
            // }
            DB::commit();
            return to_json([
                'id' => $employeeRateRequest->id,
                'saved' => true,
                'message' => 'Employee rate request updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e);
            return to_json([
                'saved' => false,
                'message' => 'Failed to update employee rate request',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function approve(Request $request, $id)
    {
        $this->authorize('access', 'employee-rate-request.approve || employee-new-rate-request.approve');

        
        $employeeRateRequest = EmployeeRateRequest::with('employee','role','company')->authorizedCompanies('company_id')->findOrFail($id);
        if(!$employeeRateRequest){
            return to_json([
                'model' => null,
                'message' => 'Employee rate request not found',
            ], 404);
        }
        $employee = Employee::find($employeeRateRequest->employee_id);

        if($employee->employee_type != 'Completed' && (!$employee->check_name)){
            return to_json([
                'model' => null,
                'message' => 'Employee first name, last name and check name are required',
            ], 400);
        }

        $shouldNotifyEmployeeHired = false;

        DB::beginTransaction();
        try {
            $userRole = Auth::user()->role->name;
            $approvalFields=[];
            if($userRole == 'HR'){
                $approvalFields = [
                    'hr_approved_by' => Auth::id(),
                    'hr_approved_at' => now(),
                    'hr_approved' => true,
                    'status' => 'approved',
                ];
            }else if($userRole == 'admin' || $userRole == 'superadmin'){
                $approvalFields = [
                    'admin_approved_by' => Auth::id(),
                    'admin_approved_at' => now(),
                    'admin_approved' => true,
                    'status' => 'approved',
                ];
            }else if($employeeRateRequest->rate_type == 'Payroll Slab' || $employeeRateRequest->rate_type == 'Payroll Regular' || $employeeRateRequest->rate_type == 'Payroll 1099'){
                $approvalFields = [
                    'do_approved_by' => Auth::id(),
                    'do_approved_at' => now(),
                    'do_approved' => true,
                    'status' => 'approved',
                ];
            }else {
                $approvalFields = [
                    'do_approved_by' => Auth::id(), 
                    'do_approved_at' => now(),
                    'do_approved' => true,
                    'status' => 'approved',
                ];
            }





            $employeeRateRequest->update(array_merge($approvalFields, ['updated_at' => now()]));

            
            if( $employeeRateRequest->status == 'approved'){
                if($employeeRateRequest->employee_rate_id){

                    $employeeRate = EmployeeRates::with('company')->authorizedCompanies('company_id')->findOrFail($employeeRateRequest->employee_rate_id);
                    if(!$employeeRate){
                        DB::rollBack();
                        return to_json([
                            'model' => null,
                            'message' => 'Employee rate not found',
                        ], 404);
                    }
                    $employeeRate->update([
                        'employee_id' => $employeeRateRequest->employee_id,
                        'role_id' => $employeeRateRequest->role_id,
                        'pay_type' => $employeeRateRequest->pay_type,
                        'rate_type' => $employeeRateRequest->rate_type,
                        'ten99_rate' => $employeeRateRequest->ten99_rate,
                        'payroll_type' => $employeeRateRequest->payroll_type ?? null,
                        'rate' => $employeeRateRequest->rate,
                        'slab_first_hours' => $employeeRateRequest->slab_first_hours,
                        'slab_rest_rate' => $employeeRateRequest->slab_rest_rate,
                        'payroll_rate' => $employeeRateRequest->payroll_rate,
                        'payroll_hours' => $employeeRateRequest->payroll_hours,
                        'payroll_hours_type' => $employeeRateRequest->payroll_hours_type,
                        'check_payment_type' => $employeeRateRequest->check_payment_type,
                        'check_payment_amount' => $employeeRateRequest->check_payment_amount,
                        'effective_date' => $employeeRateRequest->effective_date,
                        'till_date' => $employeeRateRequest->till_date,
                        'company_id' => $employeeRateRequest->company_id,
                        'updated_at' => now(),
                    ]);
                } 
                else{
                    $employeeRate = EmployeeRates::create([
                        'employee_id' => $employeeRateRequest->employee_id,
                        'role_id' => $employeeRateRequest->role_id,
                        'pay_type' => $employeeRateRequest->pay_type,
                        'rate_type' => $employeeRateRequest->rate_type,
                        'payroll_type' => $employeeRateRequest->payroll_type ?? null,
                        'rate' => $employeeRateRequest->rate,

                        'slab_first_hours' => $employeeRateRequest->slab_first_hours,
                        'slab_rest_rate' => $employeeRateRequest->slab_rest_rate,
                        'payroll_rate' => $employeeRateRequest->payroll_rate,
                        'payroll_hours' => $employeeRateRequest->payroll_hours,
                        'payroll_hours_type' => $employeeRateRequest->payroll_hours_type,
                        'check_payment_type' => $employeeRateRequest->check_payment_type,
                        'check_payment_amount' => $employeeRateRequest->check_payment_amount,
                        'effective_date' => $employeeRateRequest->effective_date,
                        'till_date' => $employeeRateRequest->till_date,
                        'company_id' => $employeeRateRequest->company_id,
                        'ten99_rate' => $employeeRateRequest->ten99_rate,

                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $employeeRateRequest->update(['employee_rate_id' => $employeeRate->id]);
                }

                $shouldNotifyEmployeeHired = $employee->employee_type !== 'Completed';

                $employee->update([
                    'employee_type' => 'Completed',
                ]);

                $payrollRate = EmployeeRates::where('employee_id',$employeeRateRequest->employee_id)->whereIn('rate_type',['Payroll 1099','Payroll Regular','Payroll Slab'])->first();
                if(!$payrollRate && $employeeRateRequest->status == 'approved'){
                    $payrollRate = Employee::where('id',$employeeRateRequest->employee_id)->update(['onboarding_status' => 'verified']);
                }
                DB::commit();
                // if($payrollRate &&  $employeeRateRequest->employee->mail_sent == false && $employeeRateRequest->employee->employee_type != 'Completed'){
                //     $onboardingController = app(OnboardingController::class);
                //     $onboardingController->handleSendMailToEmployee($employeeRateRequest->employee_id);
                // }
                WeeklySummaryRecalculationService::recalculateForEmployee(['employee_id' => $employeeRateRequest->employee_id]);
            }else{
                DB::commit();
            }

            if ($shouldNotifyEmployeeHired) {
                try {
                    $employee->refresh();
                    EmployeeHiredNotificationService::notify(
                        $employee,
                        Auth::user()?->name ?? 'System'
                    );
                } catch (\Throwable $e) {
                    report($e);
                }
            }
            return to_json([
                'success' => true,
                'message' => 'Employee rate request approved successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e);
            return to_json([
                'success' => false,
                'message' => 'Failed to approve employee rate request',
            ]);
        }
    }

    public function reject(Request $request, $id)
    {
        $this->authorize('access', 'employee-rate-request.approve');

        $validated = $request->validate([
            'reason' => 'required|string|max:5000',
        ]);

        $employeeRateRequest = EmployeeRateRequest::with(['employee', 'role', 'company'])
            ->authorizedCompanies('company_id')
            ->findOrFail($id);

        if ($employeeRateRequest->status !== 'pending') {
            return to_json([
                'success' => false,
                'message' => 'Only pending rate requests can be rejected.',
            ], 422);
        }

        $employee = Employee::query()->whereKey($employeeRateRequest->employee_id)->first();

        DB::beginTransaction();
        try {

            if($employee->employee_type != 'Completed'){
                $employee->update([
                    'rejected' => true,
                    'rejected_by' => Auth::id(),
                    'rejected_at' => now(),
                    'rejection_reason' => $validated['reason'],
                ]);
            }else{
                $employeeRateRequest->update([
                    'status' => 'rejected',
                    'rejected_by' => Auth::id(),
                    'rejected_at' => now(),
                    'rejection_reason' => $validated['reason'],
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
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e);

            return to_json([
                'success' => false,
                'message' => 'Failed to reject employee rate request',
            ], 500);
        }

        if ($employee) {
            $notifyUser = null;
            if (! empty($employee->updated_by)) {
                $notifyUser = User::query()
                    ->whereKey($employee->updated_by)
                    ->whereNotNull('email')
                    ->where('email', '!=', '')
                    ->first();
            }

            if ($notifyUser) {
                try {
                    $role = $employeeRateRequest->role;
                    $company = $employeeRateRequest->company;
                    $html = view('emails.employee-rate-request-rejected', [
                        'employeeName' => $employee->pos_name ?? $employee->employee_id ?? 'N/A',
                        'companyName' => $company?->name ?? '',
                        'roleLabel' => $role ? ($role->code . ' - ' . $role->name) : '',
                        'effectiveDate' => $employeeRateRequest->effective_date
                            ? \Illuminate\Support\Carbon::parse($employeeRateRequest->effective_date)->toFormattedDateString()
                            : '',
                        'reason' => $validated['reason'],
                        'rejectedByName' => Auth::user()?->name ?? 'System',
                    ])->render();

                    MailService::sendMail(
                        $notifyUser->email,
                        'Employee rate request rejected — ' . ($employee->pos_name ?? $employee->employee_id ?? 'N/A'),
                        $html
                    );
                } catch (\Throwable $e) {
                    dd($e);
                }
            }
        }

        return to_json([
            'success' => true,
            'message' => 'Employee rate request rejected.',
        ]);
    }

    public function destroy($id)
    {
        $this->authorize('access', 'employee-rate-request.delete');
        try {
            $employeeRateRequest = EmployeeRateRequest::with('employee','role','company')->authorizedCompanies('company_id')->findOrFail($id);
            if(!$employeeRateRequest){
                return to_json([
                    'model' => null,
                    'message' => 'Employee rate request not found',
                ], 404);
            }
            $employeeRateRequest->delete();
            return to_json([
                'deleted' => true,
                'message' => 'Employee rate request deleted successfully',
            ]);
        } catch (\Exception $e) {
            return to_json([
                'deleted' => false,
                'message' => 'Failed to delete employee rate request',
            ], 500);
        }
    }

    /**
     * Format rate request for new request email.
     */
    protected function formatRateRequestForEmail(array $data, $rolesCache, $companiesCache): array
    {
        $role = $rolesCache->get($data['role_id'] ?? null);
        $company = $companiesCache->get($data['company_id'] ?? null);
        $rate = $data['rate'] ?? 0;
        return [
            'company' => $company?->name ?? '-',
            'role' => $role ? ($role->code . ' - ' . $role->name) : '-',
            'pay_type' => $data['pay_type'] ?? '-',
            'rate_type' => $data['rate_type'] ?? '-',
            'payroll_type' => $data['payroll_type'] ?? '-',
            'rate' => $rate,
            'slab_first_hours' => $data['slab_first_hours'] ?? '-',
            'slab_rest_rate' => $data['slab_rest_rate'] ?? '-',
            'rate_formatted' => '$' . number_format((float) $rate, 2),
            'effective_date' => $data['effective_date'] ?? '-',
            'till_date' => $data['till_date'] ?? '-',
            'payroll_hours' => !empty($data['payroll_hours'])
                ? $data['payroll_hours'] . ' ' . (($data['payroll_hours_type'] ?? 'fixed') === 'percentage' ? '%' : 'Hours')
                : '-',
            'check_payment_amount' => ($data['check_payment_type'] ?? 'fixed') === 'percentage'
                ? ($data['check_payment_amount'] . '%')
                : (string) ($data['check_payment_amount'] ?? '-'),
        ];
    }

    /**
     * Get rate request data for comparison (from existing model).
     */
    protected function getRateRequestForComparison(EmployeeRateRequest $req): array
    {
        $role = $req->role;
        $company = $req->company;
        return [
            'role' => $role ? ($role->code . ' - ' . $role->name) : (string) $req->role_id,
            'company' => $company?->company_name ?? (string) $req->company_id,
            'pay_type' => $req->pay_type,
            'rate_type' => $req->rate_type,
            'payroll_type' => $req->payroll_type ?? null,
            'rate' => $req->rate,
            'effective_date' => $req->effective_date,
            'till_date' => $req->till_date ?? '-',
            'slab_first_hours' => $req->slab_first_hours ?? '-',
            'slab_rest_rate' => $req->slab_rest_rate ?? '-',
            'payroll_hours' => $req->payroll_hours
                ? $req->payroll_hours . ' ' . (($req->payroll_hours_type ?? 'fixed') === 'percentage' ? '%' : 'Hours')
                : '-',
            'check_payment_amount' => ($req->check_payment_type ?? 'fixed') === 'percentage'
                ? ($req->check_payment_amount . '%')
                : (string) ($req->check_payment_amount ?? '-'),
        ];
    }

    /**
     * Build rate request data for comparison from raw array.
     */
    protected function buildRateRequestForComparison(array $data, $rolesCache, $companiesCache): array
    {
        $role = $rolesCache->get($data['role_id'] ?? null);
        $company = $companiesCache->get($data['company_id'] ?? null);
        $rate = $data['rate'] ?? 0;
        $amount = $data['check_payment_amount'] ?? 0;
        $checkType = $data['check_payment_type'] ?? 'fixed';
        return [
            'role' => $role ? ($role->code . ' - ' . $role->name) : (string) ($data['role_id'] ?? '-'),
            'company' => $company?->name ?? (string) ($data['company_id'] ?? '-'),
            'pay_type' => $data['pay_type'] ?? '-',
            'rate_type' => $data['rate_type'] ?? '-',
            'payroll_type' => $data['payroll_type'] ?? null,
            'rate' => $rate,
            'effective_date' => $data['effective_date'] ?? '-',
            'till_date' => $data['till_date'] ?? '-',
            'slab_first_hours' => $data['slab_first_hours'] ?? '-',
            'slab_rest_rate' => $data['slab_rest_rate'] ?? '-',
            'payroll_hours' => !empty($data['payroll_hours'])
                ? $data['payroll_hours'] . ' ' . (($data['payroll_hours_type'] ?? 'fixed') === 'percentage' ? '%' : 'Hours')
                : '-',
            'check_payment_amount' => $checkType === 'percentage' ? ($amount . '%') : (string) $amount,
        ];
    }

    /**
     * Check if the comparison result contains any actual changes (old !== new).
     */
    protected function hasComparisonChanges(array $comparison): bool
    {
        $changes = $comparison['changes'] ?? [];
        foreach ($changes as $change) {
            $oldVal = $change['old'] ?? null;
            $newVal = $change['new'] ?? null;
            if ((string) $oldVal !== (string) $newVal) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build changes array for comparison email.
     */
    protected function buildComparisonChanges(array $old, array $new): array
    {
        $labels = [
            'role' => 'Role',
            'company' => 'Company',
            'pay_type' => 'Pay Type',
            'rate_type' => 'Rate Type',
            'payroll_type' => 'Payroll Type',
            'rate' => 'Rate',
            'effective_date' => 'Effective Date',
            'till_date' => 'Till Date',
            'slab_first_hours' => 'Slab First Hours',
            'slab_rest_rate' => 'Slab Rest Rate',
            'payroll_hours' => 'Payroll Hours',
            'check_payment_amount' => 'Check Payment',
        ];

        $changes = [];
        $oldRateType = strtolower($old['rate_type'] ?? '');
        $newRateType = strtolower($new['rate_type'] ?? '');
        $isPayrollRegular = in_array('payroll regular', [$oldRateType, $newRateType]);
        $is1099Slab = in_array('1099 slab', [$oldRateType, $newRateType]);
        $is1099Regular = in_array('1099 regular', [$oldRateType, $newRateType]);
        $isPayrollSlab = in_array('payroll slab', [$oldRateType, $newRateType]);

        foreach ($labels as $key => $label) {
            if (
                in_array($key, ['slab_first_hours', 'slab_rest_rate']) &&
                ($isPayrollRegular || $is1099Regular) && !$isPayrollSlab && !$is1099Slab
            ) {
                continue;
            }
            if ($key === 'check_payment_amount' && $isPayrollRegular && !$isPayrollSlab && !$is1099Slab && !$is1099Regular) {
                continue;
            }
            if (in_array($key, ['payroll_hours']) && $isPayrollRegular && !$isPayrollSlab) {
                continue;
            }
            $changes[$key] = [
                'label' => $label,
                'old' => $old[$key] ?? '-',
                'new' => $new[$key] ?? '-',
            ];
        }

        return [
            'role' => $new['role'] ?? '-',
            'company' => $new['company'] ?? '-',
            'effective_date' => $new['effective_date'] ?? '-',
            'changes' => $changes,
        ];
    }

    /**
     * Validation rules for inline employee edits from the rate request form (onboarding flow).
     */
    protected function employeeProfileValidationRules(): array
    {
        return [
            'employee_profile' => 'required|array',
            'employee_profile.first_name' => 'required|string|max:255',
            'employee_profile.last_name' => 'required|string|max:255',
            'employee_profile.check_name' => 'required|string|max:255',
            'employee_profile.middle_name' => 'nullable|string|max:255',
            'employee_profile.employee_id' => 'nullable|string|max:255',
            'employee_profile.pos_name' => 'nullable|string|max:255',
            'employee_profile.hire_date' => 'nullable|date',
            'employee_profile.ssn' => 'nullable|string|max:50',
            'employee_profile.workgroup_id' => 'nullable|integer|exists:workgroup,id',
            'employee_profile.onboarding_status' => 'nullable|string|max:255',
            'employee_profile.employee_type' => 'nullable|string|max:255',
            'employee_profile.aliases' => 'nullable|array',
            'employee_profile.aliases.*.alias_employee_id' => 'nullable|string|max:255',
            'employee_profile.aliases.*.alias_name' => 'nullable|string|max:255',
            'employee_profile.phone' => 'nullable|string|max:255',
            'employee_profile.email' => 'nullable|email|max:255',
            'employee_profile.dob' => 'nullable|date',
            'employee_profile.termination_date' => 'nullable|date',
            'employee_profile.street' => 'nullable|string|max:255',
            'employee_profile.city' => 'nullable|string|max:255',
            'employee_profile.state' => 'nullable|string|max:255',
            'employee_profile.zip' => 'nullable|string|max:50',
            'employee_profile.emergency_contact_name' => 'nullable|string|max:255',
            'employee_profile.emergency_contact_phone' => 'nullable|string|max:255',
            'employee_profile.emergency_contact_relationship' => 'nullable|string|max:255',
        ];
    }

    /**
     * Persist employee profile fields submitted with a rate request.
     */
    protected function syncEmployeeFromRateRequestProfile(int $employeeId, array $profile): void
    {
        $employee = Employee::query()->whereKey($employeeId)->first();
        if (! $employee) {
            return;
        }

        $allowed = [
            'hire_date', 'employee_id', 'pos_name', 'ssn', 'workgroup_id',
            'onboarding_status', 'employee_type',
            'first_name', 'middle_name', 'last_name',
            'check_name', 'phone', 'email', 'dob', 'termination_date',
            'street', 'city', 'state', 'zip',
            'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relationship',
        ];

        $data = array_intersect_key($profile, array_flip($allowed));

        foreach (['hire_date', 'dob', 'termination_date'] as $dateField) {
            if (array_key_exists($dateField, $data) && ($data[$dateField] === '' || $data[$dateField] === null)) {
                $data[$dateField] = null;
            }
        }

        $employee->fill($data);
        $employee->save();

        if (array_key_exists('aliases', $profile)) {
            $employee->syncAliases($profile['aliases']);
        }
    }
}