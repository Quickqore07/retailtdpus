<?php

namespace App\Http\Controllers\Payroll;

use App\Http\Controllers\Controller;
use App\Models\Payroll\EmployeeHours;
use App\Models\Payroll\EmployeeHoursMissingDeleted;
use App\Models\Employee;
use App\Models\Payroll\EmployeeRates;
use App\Models\Onboarding\EmployeeHoursRequest;
use App\Models\Onboarding\EmployeeRateRequest;
use App\Models\Payroll\EmployeeWeeklySummary;
use App\Models\Settings\Company;
use App\Models\Settings\EmployeeRoles;
use App\Models\Settings\EmployeeSubRoles;
use App\Models\Settings\MinimumWage;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\EmployeeHoursRequestSubmissionService;
use App\Services\EmployeeService;
use App\Services\MailService;
use App\Services\OvertimeHoursUploadNotificationService;
use App\Services\WeeklySummaryRecalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class EmployeeHoursController extends Controller
{
    /**
     * Display a listing of employee hours
     */
    private EmployeeService $employeeService;
    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }
    public function index(Request $request)
    {
        $this->authorize('access', 'employee-hour.index');

        $companySort = false;
        $companySortDirection = 'asc';
        $employeeId = null;

        $currentDate = Carbon::now();
        $last30DaysDates = [];
        for ($i = 0; $i < 30; $i++) {
            $date = $currentDate->subDays(1)->format('Y-m-d');
            $last30DaysDates[] = $date;
        }
        $missingDates = [];
        $employeeHours = EmployeeHours::whereIn('date', $last30DaysDates)
            ->select(
                'date',
                'company_id',
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date', 'company_id')
            ->orderBy('date')
            ->get()->groupBy('date');

        $ignoreCodes = ['8000', '9000', '10011', '224455', '10000', '11000', '12000', '7000'];
        $companies = Company::selectRaw('id, CONCAT(store_number, " - ", name) as name')->authorizedCompanies('id')->whereNotIn('store_number', $ignoreCodes)->with('workgroup')->selectRaw('id, concat(store_number, " - ", name) as name, store_number, workgroup_id')->get()->keyBy('id')->toArray();
        $companyIds = array_keys($companies);

        $deletedMissing = EmployeeHoursMissingDeleted::whereIn('date', $last30DaysDates)
            ->get()
            ->groupBy(fn($item) => $item->date->format('Y-m-d'))
            ->map(fn($items) => $items->pluck('company_id')->map(fn($id) => (int) $id)->toArray());

        $allCompanyNames = [];
        foreach ($companies as $companyId => $company) {
            $allCompanyNames[] = [
                'company_id' => (int) $companyId,
                'name' => $company['workgroup']['name'] . ' - ' . ($company['name'] ?? ''),
            ];
        }
        foreach ($last30DaysDates as $date) {

            $arr = [];
            $companyData = $employeeHours[$date] ?? [];
            $deletedCompanyIds = $deletedMissing[$date] ?? [];

            if (!$companyData) {
                $companiesForDate = array_values(array_filter(
                    $allCompanyNames,
                    fn($company) => !in_array($company['company_id'], $deletedCompanyIds, true)
                ));
                if (count($companiesForDate) > 0) {
                    $missingDates[] = [
                        'date' => $date,
                        'companies' => $companiesForDate,
                    ];
                }
                continue;
            }
            $allCompanyIds = array_column($companyData->toArray(), 'company_id');
            $missingCompanyIds = array_diff($companyIds, $allCompanyIds);
            foreach ($missingCompanyIds as $companyId) {
                if (in_array((int) $companyId, $deletedCompanyIds, true)) {
                    continue;
                }
                $arr[] = [
                    'company_id' => (int) $companyId,
                    'name' => $companies[$companyId]['workgroup']['name'] . ' - ' . ($companies[$companyId]['name'] ?? ''),
                ];
            }
            if (count($arr) > 0) {
                $missingDates[] = [
                    'date' => $date,
                    'companies' => $arr,
                ];
            }
        }

        if ($request->has('employee_id')) {
            $employeeId = $request->employee_id;
        }
        if ($request->has('sort_column') && $request->sort_column == 'company.name') {
            $companySort = true;
            $companySortDirection = $request->sort_direction ?? 'asc';
            $requestData = $request->all();
            unset($requestData['sort_column']);
            unset($requestData['sort_direction']);
            $request->replace($requestData);
        }
        DB::beginTransaction();
        try {

            $employeeHours = EmployeeHours::with(['employee', 'company', 'role'])->authorizedCompanies('company_id')
                ->when($companySort, function ($query) use ($companySortDirection) {
                    return $query->join('company', 'company.id', '=', 'employee_hours.company_id')->select('employee_hours.*', 'company.name as company_name')
                        ->orderBy('company.name', $companySortDirection);
                })
                ->when($employeeId, function ($query) use ($employeeId) {
                    return $query->where('employee_id', $employeeId);
                })
                ->filter();


            $totalHours = EmployeeHours::authorizedCompanies('company_id')
                ->when($companySort, function ($query) use ($companySortDirection) {
                    return $query->join('company', 'company.id', '=', 'employee_hours.company_id')->select('employee_hours.*', 'company.name as company_name')
                        ->orderBy('company.name', $companySortDirection);
                })
                ->when($employeeId, function ($query) use ($employeeId) {
                    return $query->where('employee_id', $employeeId);
                })
                ->filter(null, ['paginate' => false])
                ->sum('total_hours');

            DB::commit();
            return to_json([
                'collection' => $employeeHours,
                'totalHours' => $totalHours,
                'missingDates' => $missingDates,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e);
            return to_json([
                'message' => 'Error fetching employee hours',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource
     */
    public function create()
    {
        $this->authorize('access', 'employee-hour.create');
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
        ];

        return to_json([
            'form' => $item,
        ]);
    }

    /**
     * Store a newly created resource in storage
     */
    public function store(Request $request)
    {
        $this->authorize('access', 'employee-hour.create');

        $payload = app(EmployeeHoursRequestSubmissionService::class)->submit($request, true);
        $status = $payload['_http_status'] ?? 200;
        unset($payload['_http_status']);

        return to_json($payload, $status);
    }

    /**
     * Display the specified resource
     */
    public function show($id)
    {
        $employeeHours = EmployeeHours::with([
            'employee',
            'company',
            'role',
            'employeeHoursRequest.hrApprovedBy',
            'employeeHoursRequest.doApprovedBy',
            'employeeHoursRequest.adminApprovedBy',
            'employeeHoursRequest.role'
        ])->authorizedCompanies('company_id')->findOrFail($id);

        if (!$employeeHours) {
            return to_json([
                'model' => null,
                'message' => 'Employee hours not found',
            ], 404);
        }
        $eow = $this->calculateEOW($employeeHours->date);
        $weekSummary = EmployeeWeeklySummary::where('employee_id', $employeeHours->employee_id)->where('is_reviewed', true)->where('role_id', $employeeHours->role_id)->where('eow',  $eow)->first();
        $employeeHours->reviewed = $weekSummary ? true : false;
        return to_json([
            'model' => $employeeHours,
        ]);
    }

    /**
     * Show the form for editing the specified resource
     */
    public function edit($id)
    {
        $employeeHours = EmployeeHours::with(['employee', 'company', 'role', 'employeeHoursRequest.employee', 'employeeHoursRequest.company', 'employeeHoursRequest.role'])
            ->authorizedCompanies('company_id')->findOrFail($id);
        if (!$employeeHours) {
            return to_json([
                'model' => null,
                'message' => 'Employee hours not found',
            ], 404);
        }
        $eow = $this->calculateEOW($employeeHours->date);
        $weekSummary = EmployeeWeeklySummary::where('employee_id', $employeeHours->employee_id)->where('is_reviewed', true)->where('role_id', $employeeHours->role_id)->where('eow',  $eow)->first();
        $employeeHours->reviewed = $weekSummary ? true : false;
        return to_json([
            'form' => $employeeHours,
        ]);
    }

    /**
     * Update the specified resource in storage
     */
    public function update(Request $request, $id)
    {
        $this->authorize('access', 'employee-hour.update');
        DB::beginTransaction();
        try {
            $employeeHours = EmployeeHours::authorizedCompanies('company_id')->findOrFail($id);
            if (!$employeeHours) {
                return to_json([
                    'model' => null,
                    'message' => 'Employee hours not found',
                ], 404);
            }

            // Store old date to update old week summary if date changes
            $oldDate = $employeeHours->date;

            $validator = Validator::make($request->all(), [
                'date' => 'required|date',
                'employee_id' => 'required',
                'company_id' => 'required|exists:company,id',
                'role_id' => 'required|exists:employee_roles,id',
                'total_hours' => 'required|numeric|min:0',
                'pay_rate' => 'required|numeric|min:0',
                'tips' => 'nullable|numeric|min:0',
                'tips_due' => 'nullable|numeric|min:0',
                'mileage_excess' => 'nullable|numeric|min:0',
                'mileage_due' => 'nullable|numeric|min:0',
                'incentive' => 'nullable|numeric|min:0',
                'bonus' => 'nullable|numeric|min:0',
                'pay_type' => 'required|in:HR,WK',
            ]);

            if ($validator->fails()) {
                return to_json([
                    'saved' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $eow = $this->calculateEOW($employeeHours->date);
            $weekSummary = EmployeeWeeklySummary::where('employee_id', $employeeHours->employee_id)->where('is_reviewed', true)->where('role_id', $employeeHours->role_id)->where('eow',  $eow)->first();
            if ($weekSummary) {
                return to_json([
                    'saved' => false,
                    'message' => 'Employee hours has already been reviewed for this week',
                ], 400);
            }

            // Check if approval-required fields changed (only these trigger HR email): date, company, role, hours, rate, tips, tips_due, mileage, mileage_due
            $approvalFields = ['date', 'company_id', 'role_id', 'total_hours', 'pay_rate', 'tips', 'tips_due', 'mileage_excess', 'mileage_due'];
            $hasApprovalChange = false;

            $oldData = $employeeHours;
            if (isset($request->employee_hours_request_id) && $request->employee_hours_request_id) {
                $oldData = EmployeeHoursRequest::find($request->employee_hours_request_id);
            }

            foreach ($approvalFields as $field) {
                $oldVal = $oldData->{$field} ?? ($field === 'date' ? null : 0);
                $newVal = $request->get($field) ?? ($field === 'date' ? null : 0);
                if ($field === 'date') {
                    $oldStr = $oldVal ? Carbon::parse($oldVal)->format('Y-m-d') : '';
                    $newStr = $newVal ? Carbon::parse($newVal)->format('Y-m-d') : '';
                    if ($oldStr !== $newStr) {
                        $hasApprovalChange = true;
                        break;
                    }
                } elseif ((string) $oldVal !== (string) $newVal) {
                    $hasApprovalChange = true;
                    break;
                }
            }

            // if ($hasApprovalChange) {
            //     // Update existing pending request or create new approval request
            //     $input = $request->all();
            //     $input['employee_id'] = $request->employee_id;
            //     $input['employee_hours_id'] = $employeeHours->id;
            //     $input['status'] = 'pending';
            //     $input['tips_due'] = $request->get('tips_due') ?? 0;
            //     $input['mileage_due'] = $request->get('mileage_due') ?? 0;

            //     $existingRequest = $request->employee_hours_request_id
            //         ? EmployeeHoursRequest::where('id', $request->employee_hours_request_id)
            //             ->where('employee_hours_id', $employeeHours->id)
            //             ->where('status', 'pending')
            //             ->first()
            //         : null;

            //     $hoursRequest = $existingRequest
            //         ? tap($existingRequest)->update($input)
            //         : EmployeeHoursRequest::create($input);
            //     $employee = $employeeHours->employee ?? Employee::find($request->employee_id);
            //     $companiesCache = Company::select('id', 'name')->get()->keyBy('id');
            //     $rolesCache = EmployeeRoles::select('id', 'name', 'code')->get()->keyBy('id');

            //     if ($employee) {
            //         $hrUsers = User::whereHas('role', fn ($q) => $q->where('name', 'HR'))
            //             ->whereNotNull('email')
            //             ->where('email', '!=', '')
            //             ->get();
            //         $requestedBy = Auth::user()?->name ?? 'System';
            //         $emailRequest = $this->formatHoursRequestForEmail($input, $companiesCache, $rolesCache);
            //         foreach ($hrUsers as $hrUser) {
            //             try {
            //                 $html = view('emails.employee-hours-requests', [
            //                     'employee' => $employee,
            //                     'requests' => [$emailRequest],
            //                     'requestedBy' => $requestedBy,
            //                 ])->render();
            //                 MailService::sendMail(
            //                     $hrUser->email,
            //                     'Employee Hours Update Request - ' . ($employee->pos_name ?? $employee->employee_id ?? 'N/A'),
            //                     $html
            //                 );
            //             } catch (\Throwable $e) {
            //                 report($e);
            //             }
            //         }
            //     }

            //     DB::commit();

            //     return to_json([
            //         'message' => 'Employee hours update has been submitted for HR approval',
            //         'saved' => true,
            //         'id' => $employeeHours->id,
            //         'approval_request_id' => $hoursRequest->id,
            //     ]);
            // }

            $input = $request->all();
            $input['employee_id'] = $request->employee_id;
            $input['tips_due'] = $request->get('tips_due') ?? 0;
            $input['mileage_due'] = $request->get('mileage_due') ?? 0;

            $employee_rates = EmployeeRates::where('employee_id', $request->employee_id)->get();

            if (collect($employee_rates)->where('role_id', $request->role_id)->where('company_id', $request->company_id)->where('pay_type', $request->pay_type)->isEmpty()) {
                $this->employeeService->createEmployeeRates($input);
            }

            $employeeHours->update($input);

            $this->updateWeeklySummary($request->date, null, $request->employee_id);

            if ($oldDate !== $request->date) {
                $this->updateWeeklySummary($oldDate, null, $request->employee_id);
            }

            DB::commit();

            return to_json([
                'message' => 'Employee hours updated successfully',
                'saved' => true,
                'id' => $employeeHours->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'Employee hours update failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage
     */
    public function destroy($id)
    {
        $this->authorize('access', 'employee-hour.delete');
        try {
            $employeeHours = EmployeeHours::authorizedCompanies('company_id')->findOrFail($id);
            if (!$employeeHours) {
                return to_json([
                    'model' => null,
                    'message' => 'Employee hours not found',
                ], 404);
            }

            $employeeHours->delete();
            WeeklySummaryRecalculationService::recalculateForEmployee(['start_date' => $employeeHours->date, 'end_date' => $employeeHours->date]);
            return to_json([
                'message' => 'Employee hours deleted successfully',
                'deleted' => true,
            ]);
        } catch (\Exception $e) {
            return to_json([
                'deleted' => false,
                'message' => 'Employee hours deletion failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove multiple resources from storage
     */
    public function destroyMultiple(Request $request)
    {
        $this->authorize('access', 'employee-hour.delete');

        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:employee_hours,id',
        ]);

        if ($validator->fails()) {
            return to_json([
                'deleted' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $ids = $request->ids;

            $dates = EmployeeHours::whereIn('id', $ids)->pluck('date')->unique()->toArray();


            $deletedCount = EmployeeHours::whereIn('id', $ids)->delete();

            $max_date = max($dates);
            $min_date = min($dates);
            WeeklySummaryRecalculationService::recalculateForEmployee(['start_date' => $min_date, 'end_date' => $max_date]);
            DB::commit();

            return to_json([
                'message' => "Successfully deleted {$deletedCount} employee hour(s)",
                'deleted' => true,
                'count' => $deletedCount,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'deleted' => false,
                'message' => 'Employee hours deletion failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search employee hours
     */
    public function search()
    {
        $employeeHours = EmployeeHours::with(['employee', 'company', 'role'])->authorizedCompanies('company_id')->filter();

        return to_json([
            'collection' => $employeeHours,
        ]);
    }

    /**
     * Get employee hours by date range
     */
    public function getByDateRange(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'employee_id' => 'nullable|exists:employee,id',
            'company_id' => 'nullable|exists:company,id',
        ]);

        $query = EmployeeHours::with(['employee', 'company', 'role'])
            ->whereBetween('date', [$request->start_date, $request->end_date]);

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        $employeeHours = $query->get();

        return to_json([
            'collection' => $employeeHours,
        ]);
    }

    /**
     * Upload employee hours (supports single or multiple files)
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required_without:files|file|' . upload_max_file_size_rule(),
            'files' => 'required_without:file|array',
            'files.*' => 'required|file|' . upload_max_file_size_rule(),
            'confirmed' => 'nullable|boolean',
        ]);
        set_time_limit(120);
        try {
            $authorized_companies = authorizedCompanies();
            if (count($authorized_companies) == 0) {
                return to_json([
                    'message' => 'No authorized companies found',
                    'uploaded' => false,
                ], 400);
            }

            // Normalize to array: support both single file and multiple files
            $files = $request->hasFile('files')
                ? (array) $request->file('files')
                : [$request->file('file')];
            $files = array_values(array_filter($files));


            $sheetData = [];

            foreach ($files as $file) {
                if ($request->boolean('confirmed')) {
                    $file->store('uploads/employee-hours', 'public');
                }
                $path = $file->getRealPath();
                $extension = $file->getClientOriginalExtension();

                // Parse based on file type
                if (strtolower($extension) === 'csv') {
                    $sheetData = array_merge($sheetData, $this->parseCsvFile($path));
                } else {
                    $sheetData = array_merge($sheetData, $this->parseExcelFile($path));
                }
            }

            if (empty($sheetData)) {
                return to_json([
                    'message' => 'No data found in uploaded file(s)',
                    'uploaded' => false,
                ], 400);
            }

            $confirmationDates = [];
            foreach ($sheetData as $row) {
                $parsedDate = $this->parseEmployeeHoursPayDate($row['Pay Date'] ?? null);
                if ($parsedDate) {
                    $confirmationDates[$parsedDate] = Carbon::parse($parsedDate)->format('m/d/Y');
                }
            }

            // First pass: return dates for user confirmation before importing
            if (! $request->boolean('confirmed')) {
                ksort($confirmationDates);
                $confirmationDates = array_values($confirmationDates);

                if (empty($confirmationDates)) {
                    return to_json([
                        'message' => 'No valid pay dates found in the uploaded sheet(s).',
                        'uploaded' => false,
                    ], 400);
                }

                return to_json([
                    'needs_confirmation' => true,
                    'dates' => $confirmationDates,
                    'message' => 'Please confirm the dates found in the uploaded sheet(s).',
                ]);
            }

            DB::beginTransaction();

            $timestamps = array_keys($confirmationDates);

            $minDate = min($timestamps);
            $maxDate = max($timestamps);

            $employees = Employee::with('aliases')->select('id', 'employee_id', 'pos_name', 'termination_date', 'workgroup_id')
                ->authorizedWorkgroup()
                ->get();


            // Set of all IDs (employee_id + alias ids) for "Existing" employee type check
            $allEmployeeIds = collect();
            foreach ($employees as $emp) {
                $allEmployeeIds = $allEmployeeIds->merge($emp->allMatchIds());
            }
            $allEmployeeIds = $allEmployeeIds->unique()->flip();

            // Use composite key (employee_id + name) to handle duplicates - include alias ids/names


            $allEmployeePosNames = collect();
            $employeeMap = [];
            foreach ($employees as $emp) {
                $ids = $emp->allMatchIds();
                $names = $emp->allMatchNames();
                foreach ($ids as $id) {
                    foreach ($names as $name) {
                        $name = preg_replace('/\s+/', ' ', trim($name));
                        $employeeMap[$id . '|' . strtolower($name)] = $emp;
                    }
                }
                foreach ($names as $name) {
                    $name = preg_replace('/\s+/', ' ', trim($name));
                    $allEmployeePosNames->push($name);
                }
            }

            $employeeRatesMap = DB::table('employee_rates')
                ->join('employee', 'employee_rates.employee_id', '=', 'employee.id')
                ->whereIn('employee.id', $employees->pluck('id'))
                ->select('employee.employee_id as employee_id', 'employee_rates.company_id', 'employee_rates.role_id', 'employee_rates.pay_type', 'employee_rates.id')
                ->get()
                ->groupBy(fn($r) => $r->company_id . '|' . $r->employee_id . '|' . $r->role_id);

            $employeeRateRequestMap = DB::table('employee_rate_requests')
                ->join('employee', 'employee_rate_requests.employee_id', '=', 'employee.id')
                ->whereIn('employee.id', $employees->pluck('id'))
                ->where('employee_rate_requests.status', 'pending')
                ->select('employee.employee_id as employee_id', 'employee_rate_requests.company_id', 'employee_rate_requests.role_id', 'employee_rate_requests.pay_type', 'employee_rate_requests.id')
                ->get()
                ->groupBy(fn($r) => $r->company_id . '|' . $r->employee_id . '|' . $r->role_id);

            unset($employees);






            $rolesByName = EmployeeRoles::select('id', 'name', 'code')->authorizedWorkgroup()->get()->keyBy('name');
            $rolesByCode = rolesByCode();

            $companies = Company::authorizedWorkgroup()->get()->keyBy('store_number');

            $pay_date = $this->parseEmployeeHoursPayDate($sheetData[0]['Pay Date'] ?? null);
            if (!$pay_date || empty($sheetData)) {
                DB::rollBack();
                return to_json([
                    'message' => empty($sheetData) ? 'No data found in uploaded file(s)' : 'Invalid pay date format',
                    'uploaded' => false,
                ], 400);
            }

            // Track existing employee hours using composite key - include employee_id_1/2/3 for alias matching

            $existingEmployeeHours = DB::table('employee_hours')
                ->whereBetween('date', [$minDate, $maxDate])
                ->whereIn('company_id', $authorized_companies)
                ->select('employee_id', 'date', 'company_id', 'role_id', 'total_hours')
                ->get()
                ->mapWithKeys(fn($r) => [
                    $r->employee_id . '|' . $r->date . '|' . $r->company_id . '|' . $r->role_id . '|' . round($r->total_hours, 2) => true,
                ])
                ->all();
            // foreach ($employeeHours as $empHour) {
            //     if ($empHour->employee) {
            //         $compositeKey = $empHour->employee_id . '|' . $empHour->date .'|'. $empHour->company_id .'|'. $empHour->role_id .'|'. $empHour->total_hours;
            //         $existingEmployeeHours[$compositeKey] = true;
            //     }
            // }
            $employee_hours = [];
            $employee_rates = [];
            $roles_data = [];
            $missing_employees = [];
            $roles_data_update = [];
            $workgroup_id = session('workgroup');
            $overtimeUploadLineItems = [];
            $rateRequests = [];
            foreach ($sheetData as $row) {
                if (!isset($rolesByCode[$row['Role Code']]) && !isset($roles_data[$row['Role Code']]) && !isset($rolesByName[$row['Employee Role']])) {
                    $roles_data[$row['Role Code']] = [
                        'name' => $row['Employee Role'] ? $row['Employee Role'] : $row['Role Code'],
                        'code' => $row['Role Code'],
                        'active' => 1,
                        'workgroup_id' => $workgroup_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ];
                } else if (isset($rolesByName[$row['Employee Role']]) && !isset($rolesByCode[$row['Role Code']]) && !isset($roles_data[$row['Role Code']])) {
                    $roles_data_update[] = [
                        'code' => $row['Role Code'],
                        'role_id' => $rolesByName[$row['Employee Role']]->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ];
                }
            }
            if (count($roles_data) > 0) {
                EmployeeRoles::insert($roles_data);
            }
            if (count($roles_data_update) > 0) {
                EmployeeSubRoles::insert($roles_data_update);
            }

            unset($roles_data, $roles_data_update, $rolesByName);

            $rolesByCode = rolesByCode();
            $missing_companies = [];


            $new_employees = [];
            $existing_employees = [];

            foreach ($sheetData as $key => $row) {

                if ($row['Pay Type'] != 'HR') {
                    $row['Pay Type'] = 'WK';
                }

                $pay_date = $this->parseEmployeeHoursPayDate($row['Pay Date'] ?? null);
                if (!$pay_date) {
                    DB::rollBack();
                    return to_json([
                        'message' => 'Invalid pay date format at row ' . $key,
                        'uploaded' => false,
                    ], 400);
                }

                $compositeKey = $row['Employee Id'] . '|' . strtolower(preg_replace('/\s+/', ' ', trim($row['Name'])));
                $store_number = trim($row['Store']);
                // Skip if this employee hour already exists for this date
                if (!isset($companies[$store_number])) {
                    $missing_companies[] = $store_number;
                    continue;
                }
                $store_id = $companies[$store_number]['id'];
                if (!in_array($store_id, $authorized_companies)) {
                    $missing_companies[] = $store_number;
                    continue;
                }
                $employee_type = null;
                $existingEmployee = $employeeMap[$compositeKey] ?? null;
                if ($existingEmployee && $existingEmployee->termination_date) {
                    continue;
                }

                if ($existingEmployee) {
                    if (isset($existingEmployeeHours[$existingEmployee->id . '|' . $pay_date . '|' . $companies[$store_number]['id'] . '|' . $rolesByCode[$row['Role Code']] . '|' . round($row['Total Hours'], 2)])) {
                        continue;
                    }
                    // Employee exists - add hours and rates
                    $key = $store_id . '-' . $existingEmployee->employee_id . '-' . $rolesByCode[$row['Role Code']];
                    if (!isset($employeeRatesMap[$store_id . '|' . $existingEmployee->employee_id . '|' . $rolesByCode[$row['Role Code']]]) && !isset($employeeRateRequestMap[$store_id . '|' . $existingEmployee->employee_id . '|' . $rolesByCode[$row['Role Code']]])) {
                        $employee_rates[$store_id . '-' . $existingEmployee->employee_id . '-' . $rolesByCode[$row['Role Code']]]  = [
                            'employee_id' => $existingEmployee->id,
                            'company_id' => $store_id,
                            'role_id' => $rolesByCode[$row['Role Code']],
                            'pay_type' => $row['Pay Type'],
                            'rate' => $row['Pay Rate'],
                            'rate_type' => 'Payroll Regular',
                            'slab_first_hours' =>  0,
                            'slab_rest_rate' =>  0,
                            'check_payment_type' => 'percentage',
                            'payroll_hours' => 0,
                            'payroll_hours_type' => 'fixed',
                            'payroll_type' => 'Direct Deposit',
                            'payroll_rate' => 0,
                            'check_payment_amount' => 100,
                            'effective_date' => $pay_date,
                            'till_date' => null,
                            'created_by' => Auth::id(),
                            'updated_by' => Auth::id(),
                        ];
                    }
                    // else if($employeeRatesMap[$companies[$row['Store']]['id']][$existingEmployee->employee_id][$rolesByCode[$row['Role Code']]]['pay_type'] == 'HR' && floatval($row['Total Hours']) == 0){
                    //     continue;
                    // }
                    $employee_hours[$pay_date . '-' . $existingEmployee->id . '-' . $rolesByCode[$row['Role Code']] . '-' . $store_id . '-' . $row['Total Hours']] = [
                        'date' => $pay_date,
                        'employee_id' => $existingEmployee->id,
                        'employee_name' => $row['Name'],
                        'company_id' => $store_id,
                        'dev_id' => $row['Dev Id'],
                        'ssn' => $row['SSN'],
                        'pay_id' => $row['Pay Id'],
                        'pay_type' => $row['Pay Type'],
                        'total_hours' => $row['Total Hours'],
                        'tips' => $row['Tips'] ? $row['Tips'] : 0,
                        'mileage_excess' => isset($row['Mileage Excess']) && $row['Mileage Excess'] != '' && $row['Mileage Excess'] != 'N/A' ? $row['Mileage Excess'] : 0,
                        'incentive' => isset($row['Incentive']) && $row['Incentive'] != '' && $row['Incentive'] != 'N/A' ? $row['Incentive'] : 0,
                        'bonus' => isset($row['Bonus']) && $row['Bonus'] != '' && $row['Bonus'] != 'N/A' ? $row['Bonus'] : 0,
                        'home_store' => isset($row['Home Store']) && $row['Home Store'] != '' && $row['Home Store'] != 'N/A' ? $row['Home Store'] : null,
                        'role_id' => $rolesByCode[$row['Role Code']],
                        'pay_rate' => $row['Pay Rate'],
                        'tips_due' => isset($row['Tips Due']) && $row['Tips Due'] != '' && $row['Tips Due'] != 'N/A' ? $row['Tips Due'] : 0,
                        'mileage_due' => isset($row['Mileage Due']) && $row['Mileage Due'] != '' && $row['Mileage Due'] != 'N/A' ? $row['Mileage Due'] : 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ];
                    $otHours = $this->overtimeHoursFromUploadRow($row, $row['Pay Type']);
                    if ($otHours > 0.00001) {
                        $overtimeUploadLineItems[] = [
                            'pos_employee_id' => (string) $row['Employee Id'],
                            'employee_name' => (string) $row['Name'],
                            'date' => $pay_date,
                            'total_hours' => (float) $row['Total Hours'],
                            'overtime_hours' => $otHours,
                            'role_id' => (int) $rolesByCode[$row['Role Code']],
                            'company_id' => (int) $store_id,
                        ];
                    }
                } else {
                    if (isset($allEmployeeIds[$row['Employee Id']]) || isset($allEmployeeIds[$row['Name']]) || $allEmployeePosNames->contains($row['Name'])) {
                        $employee_type = 'Existing';
                    } else {
                        $employee_type = 'New';
                    }
                }

                $emp_rate = [
                    'employee_id' => $row['Employee Id'],
                    'company_id' => $store_id,
                    'role_id' => $rolesByCode[$row['Role Code']],
                    'pay_type' => $row['Pay Type'],
                    'rate' => $row['Pay Rate'],
                    'rate_type' => 'Payroll Regular',
                    'payroll_type' => null,
                    'slab_first_hours' =>  0,
                    'slab_rest_rate' =>  0,
                    'check_payment_type' => 'percentage',
                    'check_payment_amount' => 100,
                    'effective_date' => $pay_date,
                    'till_date' => null,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ];
                $emp_hour = [
                    'date' => $pay_date,
                    'employee_id' => $row['Employee Id'],
                    'employee_name' => $row['Name'],
                    'company_id' => $store_id,
                    'dev_id' => $row['Dev Id'],
                    'ssn' => $row['SSN'],
                    'pay_id' => $row['Pay Id'],
                    'pay_type' => $row['Pay Type'],
                    'total_hours' => $row['Total Hours'],
                    'tips' => $row['Tips'] ? $row['Tips'] : 0,
                    'mileage_excess' => $row['Mileage Excess'] ? $row['Mileage Excess'] : 0,
                    'incentive' => $row['Incentive'] ? $row['Incentive'] : 0,
                    'bonus' => $row['Bonus'] ? $row['Bonus'] : 0,
                    'home_store' => $row['Home Store'] ? $row['Home Store'] : null,
                    'role_id' => $rolesByCode[$row['Role Code']],
                    'pay_rate' => $row['Pay Rate'],
                    'tips_due' => $row['Tips Due'] ? $row['Tips Due'] : 0,
                    'mileage_due' => $row['Mileage Due'] ? $row['Mileage Due'] : 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ];
                $hour_key = $emp_hour['date'] . '-' . $emp_hour['employee_id'] . '-' . $emp_hour['role_id'] . '-' . $store_id . '-' . $emp_hour['total_hours'];
                $rate_key = $emp_rate['role_id'] . '-' . $emp_rate['pay_type'] . '-' . $store_id;
                if (isset($missing_employees[$row['Employee Id'] . '-' . $row['Name']])) {
                    $missing_employees[$row['Employee Id'] . '-' . $row['Name']]['employee_rates'][$rate_key] = $emp_rate;
                    $missing_employees[$row['Employee Id'] . '-' . $row['Name']]['employee_hours'][$hour_key] = $emp_hour;
                } else if ($employee_type) {
                    $missing_employees[$row['Employee Id'] . '-' . $row['Name']] = [
                        'employee_id' => $row['Employee Id'],
                        'pos_name' => $row['Name'],
                        'check_name' => $row['Name'],
                        'company_id' => $store_id,
                        'workgroup_id' => $companies[$row['Store']]['workgroup_id'],
                        'active' => true,
                        'employee_type' => $employee_type,
                        'ssn' => $row['SSN'],
                        'hire_date' => $pay_date,
                        'created_at' => now(),
                        'updated_at' => now(),
                        'employee_rates' => [$rate_key => $emp_rate],
                        'employee_hours' => [$hour_key => $emp_hour],
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ];
                    if ($employee_type == 'New') {
                        $new_employees[] = [
                            'employee_id' => $row['Employee Id'],
                            'name' => $row['Name'],
                        ];
                    }
                    if ($employee_type == 'Existing') {
                        $existing_employees[] = [
                            'employee_id' => $row['Employee Id'],
                            'name' => $row['Name'],
                        ];
                    }
                }

                if (!$existingEmployee && $employee_type) {
                    $otHours = $this->overtimeHoursFromUploadRow($row, $row['Pay Type']);
                    if ($otHours > 0.00001) {
                        $overtimeUploadLineItems[] = [
                            'pos_employee_id' => (string) $row['Employee Id'],
                            'employee_name' => (string) $row['Name'],
                            'date' => $pay_date,
                            'total_hours' => (float) $row['Total Hours'],
                            'overtime_hours' => $otHours,
                            'role_id' => (int) $rolesByCode[$row['Role Code']],
                            'company_id' => (int) $store_id,
                        ];
                    }
                }
            }
            if (count($missing_employees) > 0) {
                $maxIdsBefore = Employee::max('id') ?? 0;
                Employee::insert(collect($missing_employees)->map(function ($employee) {
                    return [
                        'employee_id' => $employee['employee_id'],
                        'pos_name' => $employee['pos_name'],
                        'check_name' => $employee['check_name'],
                        'company_id' => $employee['company_id'],
                        'workgroup_id' => $employee['workgroup_id'],
                        'active' => $employee['active'],
                        'employee_type' => $employee['employee_type'],
                        'ssn' => $employee['ssn'],
                        'hire_date' => $employee['hire_date'],
                        'created_at' => now(),
                        'updated_at' => now(),
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                    ];
                })->toArray());
                $insertedIds = Employee::where('id', '>', $maxIdsBefore)->pluck('id')->toArray();
                ActivityLogService::logBulkImport('employee', ['ids' => $insertedIds], "Min date: {$minDate}, Max date: {$maxDate}");

                if (!empty($insertedIds)) {
                    $duplicateInsertedEmployeeIds = Employee::query()
                        ->from('employee as inserted_employee')
                        ->whereIn('inserted_employee.id', $insertedIds)
                        ->whereExists(function ($query) {
                            $query->select(DB::raw(1))
                                ->from('employee as existing_employee')
                                ->whereColumn('existing_employee.id', '>', 'inserted_employee.id') // 👈 KEY FIX
                                ->where(function ($q) {
                                    $q->whereColumn('inserted_employee.employee_id', 'existing_employee.employee_id')
                                        ->orWhereColumn('inserted_employee.pos_name', 'existing_employee.pos_name');
                                });
                        })
                        ->pluck('inserted_employee.id')
                        ->toArray();

                    if (!empty($duplicateInsertedEmployeeIds)) {
                        Employee::whereIn('id', $duplicateInsertedEmployeeIds)
                            ->update(['employee_type' => 'Existing']);

                        $newEmployees = Employee::whereIn('id', $duplicateInsertedEmployeeIds)
                            ->whereIn('employee_type', ['New', 'Existing'])
                            ->select('id', 'employee_id', 'pos_name')
                            ->get();
                        foreach ($newEmployees as $emp) {
                            $existing_employees[] = [
                                'employee_id' => $emp->employee_id,
                                'name' => $emp->pos_name,
                            ];
                        }
                    }
                }

                $newEmployees = Employee::whereIn('id', $insertedIds)
                    ->whereIn('employee_type', ['New', 'Existing'])
                    ->select('id', 'employee_id', 'pos_name')
                    ->get();

                $newlyCreatedEmployees = [];

                // $newEmployees = Employee::whereIn('employee_type', ['New', 'Existing'])->where('hire_date', $pay_date)->get();
                foreach ($newEmployees as $emp) {
                    $compositeKey = $emp->employee_id . '|' . strtolower($emp->pos_name);
                    $newlyCreatedEmployees[$compositeKey] = $emp;
                }

                foreach ($missing_employees as $key => $missing_employee) {
                    $compositeKey = $missing_employee['employee_id'] . '|' . strtolower($missing_employee['pos_name']);
                    $employee = $newlyCreatedEmployees[$compositeKey] ?? null;

                    if ($employee) {
                        foreach ($missing_employee['employee_hours'] as $employee_hour) {
                            $employee_hours[] = array_merge($employee_hour, [
                                'employee_id' => $employee->id,
                            ]);
                        }
                        foreach ($missing_employee['employee_rates'] as $employee_rate) {
                            $employee_rates[] = array_merge($employee_rate, [
                                'employee_id' => $employee->id,
                            ]);
                        }
                    }
                }
            }

            // Map newly created employees by composite key


            if (count($employee_rates) > 0) {
                foreach ($employee_rates as $rate) {
                    $checkPaymentAmount = $rate['check_payment_amount'] ?? 0;
                    if (($rate['check_payment_type'] ?? 'fixed') === 'percentage' && $checkPaymentAmount > 100) {
                        $checkPaymentAmount = 100;
                    }
                    $rateRequests[] = [
                        'employee_rate_id' => null,
                        'employee_id' => $rate['employee_id'],
                        'company_id' => $rate['company_id'],
                        'role_id' => $rate['role_id'],
                        'pay_type' => $rate['pay_type'],
                        'rate_type' => $rate['rate_type'] ?? 'Payroll Regular',
                        'payroll_type' => $rate['payroll_type'] ?? null,
                        'rate' => $rate['rate'],
                        'slab_first_hours' => $rate['slab_first_hours'] ?? 0,
                        'slab_rest_rate' => $rate['slab_rest_rate'] ?? 0,
                        'effective_date' => $rate['effective_date'],
                        'till_date' => $rate['till_date'] ?? null,
                        'payroll_hours' => $rate['payroll_hours'] ?? 0,
                        'payroll_hours_type' => $rate['payroll_hours_type'] ?? 'fixed',
                        'check_payment_type' => $rate['check_payment_type'] ?? 'fixed',
                        'check_payment_amount' => $checkPaymentAmount,
                        'status' => 'pending',
                        'created_by' => Auth::id(),
                        'updated_by' => Auth::id(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (count($rateRequests) > 0) {
                    // EmployeeRates::insert($rateRequests);
                    EmployeeRateRequest::insert($rateRequests);
                    $companyCache = Company::select('id', 'name')->get()->keyBy('id');
                    $roleCache = EmployeeRoles::select('id', 'name', 'code')->authorizedWorkgroup()->get()->keyBy('id');

                    $employeeIds = array_unique(array_column($employee_rates, 'employee_id'));
                    $employees = Employee::whereIn('id', $employeeIds)->authorizedWorkgroup()->get()->keyBy('id');
                    $requestsByEmployee = [];
                    foreach ($employee_rates as $rate) {
                        $empId = $rate['employee_id'];
                        if (!isset($requestsByEmployee[$empId])) {
                            $requestsByEmployee[$empId] = [];
                        }
                        $r = $rate['rate'] ?? 0;
                        $requestsByEmployee[$empId][] = [
                            'company' => $companyCache->get($rate['company_id'])?->name ?? '-',
                            'role' => $roleCache->get($rate['role_id'])?->code . ' - ' . $roleCache->get($rate['role_id'])?->name ?? '-',
                            'pay_type' => $rate['pay_type'] ?? '-',
                            'rate_type' => $rate['rate_type'] ?? 'Payroll Regular',
                            'rate' => $r,
                            'rate_formatted' => '$' . number_format((float) $r, 2),
                            'slab_first_hours' => $rate['slab_first_hours'] ?? '-',
                            'slab_rest_rate' => $rate['slab_rest_rate'] ?? '-',
                            'effective_date' => $rate['effective_date'] ?? '-',
                            'till_date' => $rate['till_date'] ?? '-',
                            'payroll_hours' => isset($rate['payroll_hours']) && $rate['payroll_hours'] ? $rate['payroll_hours'] . ' ' . (($rate['payroll_hours_type'] ?? 'fixed') === 'percentage' ? '%' : 'Hours') : '-',
                            'check_payment_amount' => ($rate['check_payment_type'] ?? 'fixed') === 'percentage' ? ($rate['check_payment_amount'] . '%') : (string) ($rate['check_payment_amount'] ?? '-'),
                        ];
                    }

                    // $hrUsers = User::whereHas('role', fn ($q) => $q->where('name', 'HR'))
                    //     ->whereNotNull('email')
                    //     ->where('email', '!=', '')
                    //     ->authorizedWorkgroup()
                    //     ->get();

                    // $employeeGroups = [];
                    // foreach ($requestsByEmployee as $empId => $emailRequests) {
                    //     $employee = $employees->get($empId);
                    //     if (!$employee) {
                    //         continue;
                    //     }
                    //     $employeeGroups[] = [
                    //         'employee' => $employee,
                    //         'requests' => $emailRequests,
                    //     ];
                    // }

                    // foreach ($hrUsers as $hrUser) {
                    //     if (count($employeeGroups) === 0) {
                    //         continue;
                    //     }
                    //     try {
                    //         $requestedBy = Auth::user()?->name ?? 'System (Bulk Upload)';
                    //         $html = view('emails.employee-rate-requests', [
                    //             'employeeGroups' => $employeeGroups,
                    //             'requestedBy' => $requestedBy,
                    //         ])->render();
                    //         $n = count($employeeGroups);
                    //         $subject = $n === 1
                    //             ? 'New Employee Rate Requests - ' . ($employeeGroups[0]['employee']->pos_name ?? $employeeGroups[0]['employee']->employee_id ?? 'N/A')
                    //             : 'New Employee Rate Requests (' . $n . ' employees)';
                    //         MailService::sendMail(
                    //             $hrUser->email,
                    //             $subject,
                    //             $html
                    //         );
                    //     } catch (\Throwable $e) {
                    //         report($e);
                    //     }
                    // }
                }
            }
            $maxIdBefore = EmployeeHours::max('id') ?? 0;
            if (count($employee_hours) > 0) {
                foreach (array_chunk($employee_hours, 1000) as $chunk) {
                    EmployeeHours::insert($chunk);
                }
            }

            // Update weekly summary for all unique pay dates in the data
            if (count($employee_hours) > 0) {
                $this->updateWeeklySummary($minDate, $maxDate);

                $insertedIds = EmployeeHours::where('id', '>', $maxIdBefore)->pluck('id')->toArray();
                ActivityLogService::logBulkImport('employee_hours', ['ids' => $insertedIds], "Min date: {$minDate}, Max date: {$maxDate}");
            }

            DB::commit();
            if ($overtimeUploadLineItems !== [] && (int) $workgroup_id > 0) {
                OvertimeHoursUploadNotificationService::notifyAfterUpload(
                    $overtimeUploadLineItems,
                    (int) $workgroup_id,
                    Auth::user()?->name ?? 'System (bulk upload)'
                );
            }

            return to_json([
                'message' => 'Employee hours uploaded successfully',
                'uploaded' => true,
                'files_processed' => count($files),
                'rows_processed' => count($sheetData),
                'missing_companies' => array_values(array_unique($missing_companies)),
                'new_employees' => $new_employees,
                'existing_employees' => $existing_employees,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e);
            Log::error('Employee Hours Upload Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return to_json([
                'message' => 'Employee hours upload failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Parse employee hours Pay Date into Y-m-d, or null if invalid.
     */
    private function parseEmployeeHoursPayDate($date): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }

        $date = trim((string) $date);
        $formats = ['m/d/y', 'm/d/Y', 'm-d-Y', 'm-d-y', 'Y-m-d'];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $date)->format('Y-m-d');
            } catch (\Exception $e) {
                // try next format
            }
        }

        try {
            return Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Overtime for upload notifications: use explicit columns if present, else HR rows use hours over 40 (same idea as payroll regular HR in this controller).
     */
    private function overtimeHoursFromUploadRow(array $row, string $payType): float
    {
        $explicitKeys = ['Overtime Hours', 'OT Hours', 'Overtime', 'OT', 'Overtime Hrs'];
        foreach ($explicitKeys as $key) {
            if (! array_key_exists($key, $row)) {
                continue;
            }
            $v = $row[$key];
            if ($v === '' || $v === null || $v === 'N/A') {
                continue;
            }
            if (is_numeric($v) || (is_string($v) && is_numeric(trim($v)))) {
                return max(0.0, (float) $v);
            }
        }

        $total = (float) ($row['Total Hours'] ?? 0);
        if ($payType === 'HR') {
            return max(0.0, $total - 12);
        }

        return 0.0;
    }

    /**
     * Parse CSV file and return array of data
     */
    private function parseCsvFile($path)
    {
        $data = [];
        $headers = [];

        if (($handle = fopen($path, 'r')) !== false) {
            $rowIndex = 0;

            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                if ($rowIndex === 0) {
                    // First row as headers
                    $headers = array_map('trim', $row);
                } else {
                    // Map data with headers
                    $rowData = [];
                    foreach ($row as $index => $value) {
                        $header = $headers[$index] ?? "column_$index";
                        $rowData[$header] = trim($value);
                    }
                    $data[] = $rowData;
                }
                $rowIndex++;
            }

            fclose($handle);
        }

        return $data;
    }

    /**
     * Parse Excel file and return array of data
     * Note: This requires phpoffice/phpspreadsheet package
     */
    private function parseExcelFile($path)
    {
        // Check if PhpSpreadsheet is available
        if (!class_exists(\PhpOffice\PhpSpreadsheet\IOFactory::class)) {
            throw new \Exception('PhpSpreadsheet library not installed. Please run: composer require phpoffice/phpspreadsheet');
        }

        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            $data = [];
            $headers = [];

            foreach ($rows as $index => $row) {
                if ($index === 0) {
                    // First row as headers
                    $headers = array_map('trim', $row);
                } else {
                    // Skip empty rows
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    // Map data with headers
                    $rowData = [];
                    foreach ($row as $cellIndex => $value) {
                        $header = $headers[$cellIndex] ?? "column_$cellIndex";
                        $rowData[$header] = $value !== null ? trim((string)$value) : '';
                    }
                    $data[] = $rowData;
                }
            }

            return $data;
        } catch (\Exception $e) {
            Log::error('Excel parsing error', [
                'error' => $e->getMessage(),
                'file' => $path
            ]);
            throw new \Exception('Failed to parse Excel file: ' . $e->getMessage());
        }
    }

    /**
     * Calculate the end of week (EOW) for a given date
     * Returns the Sunday of the weekly period
     */
    private function calculateEOW($date)
    {
        $carbonDate = Carbon::parse($date);

        // Find the year start
        $yearStart = Carbon::parse($carbonDate->year . '-01-01');

        // Find the first Monday of the year or before
        $dayOfWeek = (int)$yearStart->format('N');
        $daysToMonday = $dayOfWeek === 7 ? -6 : 1 - $dayOfWeek - 7;
        $firstMonday = $yearStart->copy()->addDays($daysToMonday);

        // Calculate which weekly period this date falls into
        $diffDays = $firstMonday->diffInDays($carbonDate, false);
        $periodIndex = floor($diffDays / 7);

        // Calculate the EOW (end of week = Sunday, 6 days after Monday start)
        $eow = $firstMonday->copy()->addDays(($periodIndex * 7) + 6);

        return $eow->format('Y-m-d');
    }

    /**
     * Update weekly summary table for the given pay date
     * This aggregates employee hours by week and calculates payroll
     */
    private function updateWeeklySummary($minDate, ?string $maxDate = null, $employeeId = null)
    {
        try {
            // Calculate EOW for this pay date
            $eow = $minDate ? $this->calculateEOW($minDate) : null;
            $startDate = Carbon::parse($eow)->subDays(13)->format('Y-m-d');
            // Calculate the week period (Monday to Sunday)
            $endDate = $maxDate ? Carbon::parse($eow)->format('Y-m-d') : null;
            if ($maxDate) {
                $endDate = $this->calculateEOW($maxDate);
            }
            WeeklySummaryRecalculationService::recalculateForEmployee(['start_date' => $startDate, 'end_date' => $endDate, 'employee_id' => $employeeId]);

            // $weekData = EmployeeHours::whereBetween('date', [$weekStart, $weekEnd])
            //     ->groupBy('employee_id', 'role_id', 'company_id')
            //     ->selectRaw('
            //         employee_id, 
            //         role_id, 
            //         company_id, 
            //         sum(total_hours) as total_hours, 
            //         sum(tips) as tips, 
            //         sum(mileage_excess) as mileage_excess, 
            //         sum(incentive) as incentive, 
            //         sum(bonus) as bonus, 
            //         sum(tips_due) as tips_due, 
            //         sum(mileage_due) as mileage_due
            //     ')
            //     ->with('employee.employeeRates', 'company.state')
            //     ->get();
            // $summariesToUpsert = [];
            // foreach ($weekData as $row) {
            //     if (!$row || !$row->employee) {
            //         continue;
            //     }
            //     // Get the rate that was effective for this pay period (EOW)
            //     // Select the most recent rate where effective_date <= eow
            //     $rate = $row->employee->employeeRates
            //         ->where('role_id', $row->role_id)
            //         ->where('company_id', $row->company_id)
            //         ->filter(fn($r) => $r->effective_date <= $eow && ($r->till_date == null || $r->till_date >= $eow))
            //         ->sortByDesc('effective_date')
            //         ->first();
            //     if (!$rate) {
            //         continue;
            //     }
            //     // Calculate payroll for the week
            //     $weekCalculated = $this->calculatePayrollForSummary($row, $rate);

            //     // Get minimum wage if applicable
            //     $minWageData = $this->calculateMinimumWageForWeekly($row, $rate, $eow);

            //     $summariesToUpsert[] = [
            //         'employee_id' => $row->employee_id,
            //         'role_id' => $row->role_id,
            //         'company_id' => $row->company_id,
            //         'employee_rate_id' => $rate->id ?? null,
            //         'eow' => $eow,
            //         'pay_type' => $rate->pay_type,
            //         'total_hours' => $row->total_hours ?? 0,
            //         'regular_hours' => $weekCalculated['regular_hours'],
            //         'overtime_hours' => $weekCalculated['overtime_hours'],
            //         'tips' => $row->tips ?? 0,
            //         'mileage_excess' => $row->mileage_excess ?? 0,
            //         'incentive' => $row->incentive ?? 0,
            //         'bonus' => $row->bonus ?? 0,
            //         'tips_due' => $row->tips_due ?? 0,
            //         'mileage_due' => $row->mileage_due ?? 0,
            //         'payroll_methods' => $weekCalculated['payroll_methods'],
            //         'check_methods' => $weekCalculated['check_methods'],
            //         'instant_methods' => $weekCalculated['instant_methods'],
            //         'gross_pay' => $weekCalculated['gross_pay'],
            //         'total_earnings' => $weekCalculated['total_earnings'],
            //         'hr_pay' => $weekCalculated['hr_pay'],
            //         'rate' => $rate->rate ?? 0,
            //         'rate_type' => $rate->rate_type ?? null,
            //         'payroll_type' => $rate->payroll_type ?? null,
            //         'slab_first_hours' => $rate->slab_first_hours ?? null,
            //         'slab_rest_rate' => $rate->slab_rest_rate ?? null,
            //         'min_wage_hourly' => $minWageData['min_wage_hourly'] ?? null,
            //         'min_wage_weekly' => $minWageData['min_wage_weekly'] ?? null,
            //         'min_wage_due' => ($minWageData['min_wage_weekly'] ?? 0) - ($weekCalculated['payroll_methods'] ?? 0),
            //         // Store original calculated amounts
            //         'calculated_check_amount' => $weekCalculated['check_methods'],
            //         'calculated_instant_amount' => $weekCalculated['instant_methods'],
            //         'calculated_payroll_amount' => $weekCalculated['payroll_methods'],
            //         'created_at' => now(),
            //         'updated_at' => now(),
            //     ];
            // }
            // $employeeRates=[];
            // foreach ($summariesToUpsert as $index => $summary) {
            //     if($summary['pay_type'] == 'HR'){
            //         if (!isset($employeeRates[$summary['employee_id']. '-' . $summary['company_id']])) {
            //             $employeeRates[$summary['employee_id']. '-' . $summary['company_id']] = [];
            //         }
            //         $employeeRates[$summary['employee_id']. '-' . $summary['company_id']][] = array_merge($summary, ['index'=>$index]);
            //     }
            // }


            // foreach ($employeeRates as $key => $summaries) {
            //     // Calculate total hours across all roles for this employee
            //     $totalHours = 0;
            //     foreach ($summaries as $summary) {
            //         $totalHours += $summary['total_hours'];
            //     }
            //     // Calculate overtime hours (anything over 40 hours)
            //     $overtimeHours = max(0, $totalHours - 40);
            //     info($overtimeHours);

            //     // If no overtime, just remove the index keys and continue
            //     if ($overtimeHours <= 0) {
            //         foreach ($summaries as $summary) {
            //             unset($summariesToUpsert[$summary['index']]['index']);
            //         }
            //         continue;
            //     }

            //     // Find the summary with the highest rate
            //     $highestRateSummary = null;
            //     foreach ($summaries as $summary) {
            //         if ($highestRateSummary === null || $summary['rate'] > $highestRateSummary['rate']) {
            //             $highestRateSummary = $summary;
            //         }
            //     }
            //     info($highestRateSummary);

            //     // Reset overtime hours for all summaries and set all hours as regular
            //     foreach ($summaries as $summary) {
            //         $summariesToUpsert[$summary['index']]['overtime_hours'] = 0;
            //         unset($summariesToUpsert[$summary['index']]['index']);
            //     }

            //     // Assign all overtime to the highest rate summary
            //     if ($highestRateSummary) {
            //         $summariesToUpsert[$highestRateSummary['index']]['overtime_hours'] = $overtimeHours;
            //     }
            //     $regularHours = $totalHours - $overtimeHours;

            //     foreach ($summaries as $summary) {
            //         if($regularHours > $summary['regular_hours']){
            //             info($summary['regular_hours']);
            //             info($regularHours);

            //             $summariesToUpsert[$summary['index']]['regular_hours'] = $summary['regular_hours'];
            //             $regularHours -= $summary['regular_hours'];
            //         }else{
            //             $summariesToUpsert[$summary['index']]['regular_hours'] = $regularHours;
            //             $regularHours = 0;
            //         }
            //         $summariesToUpsert[$summary['index']]['total_hours'] =$summariesToUpsert[$summary['index']]['overtime_hours'] + $summariesToUpsert[$summary['index']]['regular_hours'];
            //     }
            // }

            // if (count($summariesToUpsert) > 0) {
            //     foreach (array_chunk($summariesToUpsert, 500) as $chunk) {
            //         EmployeeWeeklySummary::upsert(
            //             $chunk,
            //             ['employee_id', 'role_id', 'company_id', 'eow'],
            //             [
            //                 'total_hours',
            //                 'regular_hours',
            //                 'overtime_hours',
            //                 'tips',
            //                 'mileage_excess',
            //                 'incentive',
            //                 'bonus',
            //                 'tips_due',
            //                 'mileage_due',
            //                 'payroll_methods',
            //                 'check_methods',
            //                 'instant_methods',
            //                 'gross_pay',
            //                 'total_earnings',
            //                 'hr_pay',
            //                 'rate',
            //                 'rate_type',
            //                 'payroll_type',
            //                 'slab_first_hours',
            //                 'slab_rest_rate',
            //                 'min_wage_hourly',
            //                 'min_wage_weekly',
            //                 'min_wage_due',
            //                 'calculated_check_amount',
            //                 'calculated_instant_amount',
            //                 'calculated_payroll_amount',
            //                 'updated_at',
            //             ]
            //         );
            //     }
            // }

        } catch (\Exception $e) {
            Log::error('Failed to update weekly summary', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Calculate payroll methods for weekly summary
     * Same logic as PayrollReportController::calculatePayrollMethods
     */
    private function calculatePayrollForSummary($row, $rate)
    {
        $result = [
            'regular_hours' => 0,
            'overtime_hours' => 0,
            'payroll_methods' => 0,
            'check_methods' => 0,
            'instant_methods' => 0,
            'gross_pay' => 0,
            'total_earnings' => 0,
            'hr_pay' => 0,
        ];

        $total_hours = $row->total_hours ?? 0;
        $tips_due = $row->tips_due ?? 0;
        $mileage_due = $row->mileage_due ?? 0;
        $bonus = $row->bonus ?? 0;

        if ($rate->rate_type == 'Payroll Regular') {
            if ($rate->pay_type == 'WK') {
                $result['regular_hours'] = $total_hours;
                $result['overtime_hours'] = 0;
            } else {
                $result['overtime_hours'] = ($total_hours - 40) > 0 ? ($total_hours - 40) : 0;
                $result['regular_hours'] = $total_hours - $result['overtime_hours'];
            }

            $normal_pay = $rate->pay_type == 'HR' ? $result['regular_hours'] * $rate->rate : $rate->rate;
            $overtime_pay = $rate->pay_type == 'HR' ? $result['overtime_hours'] * $rate->rate * 1.5 : 0;

            $result['payroll_methods'] = $normal_pay + $overtime_pay + $tips_due + $mileage_due;
            $result['check_methods'] = 0;
            $result['instant_methods'] = 0;
        } else if ($rate->rate_type == 'Payroll Slab') {
            if ($rate->pay_type == 'WK') {
                $result['regular_hours'] = $total_hours;
                $result['overtime_hours'] = 0;
            } else {
                $result['overtime_hours'] = ($total_hours - $rate->slab_first_hours) > 0 ? ($total_hours - $rate->slab_first_hours) : 0;
                $result['regular_hours'] = $total_hours - $result['overtime_hours'];
            }

            $normal_pay = $rate->pay_type == 'HR' ? $result['regular_hours'] * $rate->rate : $rate->rate;
            $overtime_pay = $rate->pay_type == 'HR' ? $result['overtime_hours'] * $rate->slab_rest_rate : 0;
            $total_pay = $normal_pay + $overtime_pay;

            if (!empty($rate->payroll_hours)) {
                $regular_payroll_hours = min($result['regular_hours'], $rate->payroll_hours);
                $remaining_hours = max($rate->payroll_hours - $regular_payroll_hours, 0);
                $overtime_payroll_hours = min($result['overtime_hours'], $remaining_hours);

                $result['payroll_methods'] = ($regular_payroll_hours * $rate->rate) + ($overtime_payroll_hours * $rate->slab_rest_rate);
            } else {
                $result['payroll_methods'] = $total_pay;
            }

            $remaining_amount = $total_pay - $result['payroll_methods'] + $tips_due + $mileage_due;

            if ($remaining_amount > 0 && $rate->check_payment_type == 'percentage') {
                $check_payment_amount = $remaining_amount * $rate->check_payment_amount / 100;
                $result['check_methods'] = $remaining_amount > $check_payment_amount ? $check_payment_amount : $remaining_amount;
            } else if ($remaining_amount > 0 && $rate->check_payment_type == 'fixed') {
                $check_payment_amount = $rate->check_payment_amount;
                $result['check_methods'] = $remaining_amount > $check_payment_amount ? $check_payment_amount : $remaining_amount;
            } else {
                $result['check_methods'] = 0;
            }

            $result['instant_methods'] = $remaining_amount - $result['check_methods'];
        } else if ($rate->rate_type == '1099 Regular') {
            if ($rate->pay_type == 'WK') {
                $result['regular_hours'] = $total_hours;
                $result['overtime_hours'] = 0;
            } else {
                $result['overtime_hours'] = ($total_hours - 40) > 0 ? ($total_hours - 40) : 0;
                $result['regular_hours'] = $total_hours - $result['overtime_hours'];
            }

            $normal_pay = $rate->pay_type == 'HR' ? $result['regular_hours'] * $rate->rate : $rate->rate;
            $overtime_pay = $rate->pay_type == 'HR' ? $result['overtime_hours'] * $rate->rate * 1.5 : 0;
            $total_pay = $normal_pay + $overtime_pay + $tips_due + $mileage_due;

            if ($total_pay > 0 && $rate->check_payment_type == 'percentage') {
                $check_payment_amount = $total_pay * $rate->check_payment_amount / 100;
                $result['check_methods'] = $total_pay > $check_payment_amount ? $check_payment_amount : $total_pay;
            } else if ($total_pay > 0 && $rate->check_payment_type == 'fixed') {
                $check_payment_amount = $rate->check_payment_amount;
                $result['check_methods'] = $total_pay > $check_payment_amount ? $check_payment_amount : $total_pay;
            } else {
                $result['check_methods'] = 0;
            }

            $result['payroll_methods'] = 0;
            $result['instant_methods'] = $total_pay - $result['check_methods'];
        } else if ($rate->rate_type == '1099 Slab') {
            if ($rate->pay_type == 'WK') {
                $result['regular_hours'] = $total_hours;
                $result['overtime_hours'] = 0;
            } else {
                $result['overtime_hours'] = ($total_hours - $rate->slab_first_hours) > 0 ? ($total_hours - $rate->slab_first_hours) : 0;
                $result['regular_hours'] = $total_hours - $result['overtime_hours'];
            }

            $normal_pay = $rate->pay_type == 'HR' ? $result['regular_hours'] * $rate->rate : $rate->rate;
            $overtime_pay = $rate->pay_type == 'HR' ? $result['overtime_hours'] * $rate->slab_rest_rate : 0;
            $total_pay = $normal_pay + $overtime_pay + $tips_due + $mileage_due;

            if ($total_pay > 0 && $rate->check_payment_type == 'percentage') {
                $check_payment_amount = $total_pay * $rate->check_payment_amount / 100;
                $result['check_methods'] = $total_pay > $check_payment_amount ? $check_payment_amount : $total_pay;
            } else if ($total_pay > 0 && $rate->check_payment_type == 'fixed') {
                $check_payment_amount = $rate->check_payment_amount;
                $result['check_methods'] = $total_pay > $check_payment_amount ? $check_payment_amount : $total_pay;
            } else {
                $result['check_methods'] = 0;
            }

            $result['payroll_methods'] = 0;
            $result['instant_methods'] = $total_pay - $result['check_methods'];
        }

        $result['gross_pay'] = $result['payroll_methods'] + $result['check_methods'] + $result['instant_methods'] - $tips_due - $mileage_due;
        $result['total_earnings'] = $result['gross_pay'] + $bonus + $tips_due + $mileage_due;
        $result['hr_pay'] = $total_hours > 0 ? $result['total_earnings'] / $total_hours : 0;

        return $result;
    }

    /**
     * Calculate minimum wage for weekly period
     */
    private function calculateMinimumWageForWeekly($row, $rate, $eow)
    {
        $result = [
            'min_wage_hourly' => null,
            'min_wage_weekly' => null,
            'min_wage_due' => 0,
        ];

        if (!$row->company || !$row->company->state_id) {
            return $result;
        }

        // Only calculate for Payroll types
        if (!in_array($rate->rate_type, ['Payroll Regular', 'Payroll Slab'])) {
            return $result;
        }

        $year = Carbon::parse($eow)->year;
        $minWage = MinimumWage::latestItemForState($row->company->state_id, $eow);
        // ->where('year', $year)
        // ->first();
        if ($minWage && isset($minWage->minimum_wage)) {
            $result['min_wage_hourly'] = $minWage->minimum_wage;
            // For weekly, use total hours for this week only
            $result['min_wage_weekly'] = $minWage->minimum_wage * ($row->total_hours ?? 0);
        }

        return $result;
    }

    /**
     * Export employee hours to Excel
     */
    public function export(Request $request)
    {
        $this->authorize('access', 'employee-hour.index');

        $companySort = false;
        $companySortDirection = 'asc';

        $request->query->set('page', 1);
        $request->query->set('limit', 1000);

        if ($request->has('sort_column') && $request->sort_column == 'company.name') {
            $companySort = true;
            $companySortDirection = $request->sort_direction ?? 'asc';
            $requestData = $request->all();
            unset($requestData['sort_column']);
            unset($requestData['sort_direction']);
            $request->replace($requestData);
        }
        // Build query with filters - use export() scope instead of filter() to avoid pagination
        $query = EmployeeHours::with(['employee.employeeRates', 'company', 'role'])->authorizedCompanies('company_id')
            ->when($companySort, function ($query) use ($companySortDirection) {
                return $query->join('company', 'company.id', '=', 'employee_hours.company_id')->select('employee_hours.*', 'company.name as company_name')
                    ->orderBy('company.name', $companySortDirection);
            });
        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        // Use export() method to get all records without pagination
        $employeeHours = $query->export($request->all());
        // Increase memory limit and execution time for large exports
        ini_set('memory_limit', '1024M'); // Increased to 1GB
        set_time_limit(600); // 10 minutes

        // Disable garbage collection during export for better performance
        gc_disable();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = [
            'Date',
            'Employee ID',
            'Employee Name',
            'Company',
            'SSN',
            'Role',
            'Pay Type',
            'Rate Type',
            'Total Hours',
            'Pay Rate',
            'Tips',
            'Mileage Excess',
            'Incentive',
            'Bonus',
            'Tips Due',
            'Mileage Due',
            'Gross Pay',
            'Created At'
        ];

        // Style the header row
        $headerRange = 'A1:R1';
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle($headerRange)->getFont()->getColor()->setARGB('FFFFFFFF');

        // Add employee hours data
        $rowNumber = 2;
        $batchData = [];
        $batchSize = 2000; // Increased batch size for better performance

        // Pre-build rate type cache to avoid repeated calculations
        $rateTypeCache = [];

        foreach ($employeeHours as $hours) {
            // Use cache for rate type calculation
            $cacheKey = $hours->employee_id . '-' . $hours->company_id . '-' . $hours->date;
            if (!isset($rateTypeCache[$cacheKey])) {
                $rateTypeCache[$cacheKey] = $this->calculateRateTypeFast($hours);
            }
            $rateType = $rateTypeCache[$cacheKey];

            $grossPay = ($hours->total_hours * $hours->pay_rate) +
                $hours->tips + $hours->mileage_excess +
                $hours->incentive + $hours->bonus;

            $batchData[] = [
                $hours->date ?? '',
                $hours->employee->employee_id ?? '',
                $hours->employee_name ?? $hours->employee->pos_name ?? '',
                $hours->company->name,
                $hours->ssn ?? '',
                $hours->role ? ($hours->role->code ? $hours->role->code . ' - ' : '') . $hours->role->name : '',
                $hours->pay_type ?? '',
                $rateType,
                number_format($hours->total_hours, 2),
                number_format($hours->pay_rate, 2),
                number_format($hours->tips, 2),
                number_format($hours->mileage_excess, 2),
                number_format($hours->incentive, 2),
                number_format($hours->bonus, 2),
                number_format($hours->tips_due, 2),
                number_format($hours->mileage_due, 2),
                number_format($grossPay, 2),
                $hours->created_at ? $hours->created_at->format('Y-m-d H:i:s') : ''
            ];

            // Write in batches for better performance
            if (count($batchData) >= $batchSize) {
                $sheet->fromArray($batchData, null, 'A' . $rowNumber);
                $rowNumber += count($batchData);
                $batchData = [];
            }
        }

        // Write any remaining data
        if (count($batchData) > 0) {
            $sheet->fromArray($batchData, null, 'A' . $rowNumber);
        }

        // Remove autosize for faster generation - use fixed widths instead
        $columnWidths = [
            'A' => 12,
            'B' => 12,
            'C' => 25,
            'D' => 25,
            'E' => 12,
            'F' => 20,
            'G' => 10,
            'H' => 15,
            'I' => 12,
            'J' => 12,
            'K' => 10,
            'L' => 15,
            'M' => 12,
            'N' => 10,
            'O' => 10,
            'P' => 12,
            'Q' => 12,
            'R' => 20
        ];
        foreach ($columnWidths as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }

        // Generate file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $fileName = 'employee_hours_export_' . date('Y-m-d_His') . '.xlsx';
        $tempFile = storage_path('app/' . $fileName);

        $writer->save($tempFile);

        gc_enable();

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    private function calculateRateType($hours)
    {
        $employeeRate = null;
        foreach ($hours->employee->employeeRates as $rate) {
            if ($rate->effective_date <= $hours->date && $hours->company_id === $rate->company_id && ($rate->till_date == null || $rate->till_date >= $hours->date)) {
                $employeeRate = $rate;
                break;
            }
        }
        return $employeeRate ? $employeeRate->rate_type : 'N/A';
    }

    private function calculateRateTypeFast($hours)
    {
        if (!$hours->employee || !$hours->employee->employeeRates) {
            return 'N/A';
        }

        $employeeRate = null;
        $latestDate = null;

        foreach ($hours->employee->employeeRates as $rate) {
            if (
                $rate->effective_date <= $hours->date &&
                $hours->company_id === $rate->company_id &&
                ($rate->till_date == null || $rate->till_date >= $hours->date) &&
                ($latestDate === null || $rate->effective_date > $latestDate)
            ) {
                $employeeRate = $rate;
                $latestDate = $rate->effective_date;
            }
        }

        return $employeeRate ? $employeeRate->rate_type : 'N/A';
    }

    /**
     * Dismiss a missing company/date from the missing list.
     */
    public function deleteMissingCompany(Request $request)
    {
        $this->authorize('access', 'employee-hour.index');

        $validated = $request->validate([
            'date' => 'required|date',
            'company_id' => 'required|integer|exists:company,id',
        ]);

        $deleted = EmployeeHoursMissingDeleted::firstOrCreate(
            [
                'date' => $validated['date'],
                'company_id' => $validated['company_id'],
            ],
            [
                'deleted_by' => Auth::id(),
            ]
        );

        return to_json([
            'message' => 'Missing company deleted successfully',
            'item' => $deleted,
        ]);
    }

    /**
     * List deleted missing company/date entries for the last 30 days.
     */
    public function deletedMissingCompanies()
    {
        $this->authorize('access', 'employee-hour.index');

        $last30DaysDates = [];
        $currentDate = Carbon::now();
        for ($i = 0; $i < 30; $i++) {
            $last30DaysDates[] = $currentDate->subDays(1)->format('Y-m-d');
        }

        $items = EmployeeHoursMissingDeleted::with(['company.workgroup'])
            ->authorizedCompanies('company_id')
            ->whereIn('date', $last30DaysDates)
            ->orderByDesc('date')
            ->orderBy('company_id')
            ->get()
            ->map(function ($item) {
                $company = $item->company;
                $workgroupName = $company?->workgroup?->name ?? '';
                $companyName = $company?->name ?? '';

                return [
                    'id' => $item->id,
                    'date' => $item->date?->format('Y-m-d'),
                    'company_id' => $item->company_id,
                    'company' => trim($workgroupName . ' - ' . $companyName, ' -'),
                    'deleted_at' => $item->created_at?->format('Y-m-d H:i:s'),
                ];
            });

        return to_json([
            'collection' => $items,
        ]);
    }

    /**
     * Format hours request for HR approval email.
     */
    private function formatHoursRequestForEmail(array $data, $companiesCache, $rolesCache): array
    {
        $company = $companiesCache->get($data['company_id'] ?? null);
        $role = $rolesCache->get($data['role_id'] ?? null);
        return [
            'company' => $company?->name ?? '-',
            'role' => $role ? ($role->code . ' - ' . $role->name) : '-',
            'date' => $data['date'] ?? '-',
            'total_hours' => $data['total_hours'] ?? '-',
            'pay_rate' => '$' . number_format((float) ($data['pay_rate'] ?? 0), 2),
            'tips' => '$' . number_format((float) ($data['tips'] ?? 0), 2),
            'tips_due' => '$' . number_format((float) ($data['tips_due'] ?? 0), 2),
            'mileage_excess' => '$' . number_format((float) ($data['mileage_excess'] ?? 0), 2),
            'mileage_due' => '$' . number_format((float) ($data['mileage_due'] ?? 0), 2),
        ];
    }
}
