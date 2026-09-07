<?php

namespace App\Http\Controllers;

use App\Models\DataEntry\DailySale;
use App\Models\DataEntry\FoodPurchase;
use App\Models\DataEntry\RoyaltyFee;
use App\Models\Employee;
use App\Models\Onboarding\EmployeeRateRequest;
use App\Models\Onboarding\EmployeeHoursRequest;
use App\Models\Onboarding\OnboardingList;
use App\Models\Onboarding\OnboardingLogs;
use App\Models\Payroll\EmployeeWeeklySummary;
use App\Models\Settings\FundRequirement;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\WorkbrightService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DashboardController extends Controller
{

    protected $workbrightService;

    public function __construct(WorkbrightService $workbrightService)
    {
        $this->workbrightService = $workbrightService;
    }

    /**
     * Get dashboard statistics: new hires, pending rate requests, pending hours requests.
     */

    public function stats(Request $request)
    {
        $userId = Auth::id();
        $user = User::find($userId);
        $permissions = $user->userRolePermissions();



        $authorizedCompanies = authorizedCompanies();

        if (in_array('employee.show', $permissions) && session('workgroup')) {
            $newHiresCount = Employee::where(function ($q) {

                $q->where('employee_type', 'New')->where(function ($q2) {
                    $q2->orWhereDoesntHave('onboardingList')->orWhere('mail_sent', 0)->orWhere('rejected', true);
                })->where('workgroup_id', session('workgroup'));
            })
                ->where(function ($q) {
                    $q->whereHas('employeeRates', function ($q) {
                        $q->authorizedCompanies('company_id');
                    })
                        ->orWhereHas('employeeRatesRequests', function ($q) {
                            $q->authorizedCompanies('company_id');
                        });
                })
                ->count();
        } else {
            $newHiresCount = 0;
        }

        if (in_array('employee-rate-request.show', $permissions) && session('workgroup')) {
            $rateRequestsPendingCount = EmployeeRateRequest::where('status', 'pending')
                ->authorizedCompanies('company_id')
                ->where(function ($q) {
                    $q->whereDoesntHave('employee', function ($query) {
                        $query->where('rejected', true)->orWhere('onboarding_status', '=', 'pending');
                    })->orWhereHas('employee', function ($query) {
                        $query->where('employee_type', 'Completed');
                    });
                })
                ->count();
        } else {
            $rateRequestsPendingCount = 0;
        }

        if (in_array('employee-rate-request.show', $permissions) && session('workgroup')) {
            $rateRequestsPendingHrCount = EmployeeRateRequest::where('status', 'pending')
                ->authorizedCompanies('company_id')
                ->where(function ($q) {
                    $q->whereNull('hr_approved')->orWhere('hr_approved', false);
                })->where(function ($q) {
                    $q->whereDoesntHave('employee', function ($query) {
                        $query->where('rejected', true)->orWhere('onboarding_status', '=', 'pending');
                    })->orWhereHas('employee', function ($query) {
                        $query->where('employee_type', 'Completed');
                    });
                })
                ->count();
        } else {
            $rateRequestsPendingHrCount = 0;
        }

        if (in_array('employee-rate-request.show', $permissions) && session('workgroup')) {
            $rateRequestsPendingDoCount = EmployeeRateRequest::where('status', 'pending')
                ->authorizedCompanies('company_id')
                ->where(function ($q) {
                    $q->whereNull('do_approved')->orWhere('do_approved', false);
                })
                ->count();
        } else {
            $rateRequestsPendingDoCount = 0;
        }

        if (in_array('employee-hours-request.show', $permissions) && session('workgroup')) {
            $hoursRequestsPendingCount = EmployeeHoursRequest::where('status', 'pending')
                ->authorizedCompanies('company_id')
                ->count();
        } else {
            $hoursRequestsPendingCount = 0;
        }

        if (in_array('employee-hours-request.show', $permissions) && session('workgroup')) {
            $hoursRequestsPendingHrCount = EmployeeHoursRequest::where('status', 'pending')
                ->authorizedCompanies('company_id')
                ->where(function ($q) {
                    $q->whereNull('hr_approved')->orWhere('hr_approved', false);
                })
                ->count();
        } else {
            $hoursRequestsPendingHrCount = 0;
        }

        if (in_array('employee-hours-request.show', $permissions) && session('workgroup')) {
            $hoursRequestsPendingDoCount = EmployeeHoursRequest::where('status', 'pending')
                ->authorizedCompanies('company_id')
                ->where(function ($q) {
                    $q->whereNull('do_approved')->orWhere('do_approved', false);
                })
                ->count();
        } else {
            $hoursRequestsPendingDoCount = 0;
        }

        return response()->json([
            'new_hires' => $newHiresCount,
            'rate_requests_pending' => $rateRequestsPendingCount,
            'rate_requests_pending_hr' => $rateRequestsPendingHrCount,
            'rate_requests_pending_do' => $rateRequestsPendingDoCount,
            'hours_requests_pending' => $hoursRequestsPendingCount,
            'hours_requests_pending_hr' => $hoursRequestsPendingHrCount,
            'hours_requests_pending_do' => $hoursRequestsPendingDoCount,
        ]);
    }

    public function workbrightStats(Request $request)
    {
        $this->authorize('access', 'workbright-dashboard.show');

        $stats = OnboardingList::authorizedCompanies('company_id')->selectRaw("
            COUNT(CASE 
                WHEN tnc = 0 
                AND status NOT IN ('waiting_for_section_2_verification','section_2_verification_done','employee_athorized','verified')
                THEN 1 END) as workbright_in_progress_count,

            COUNT(CASE 
                WHEN tnc = 0 
                AND i9_completed = 1 
                AND w4_completed = 1
                AND status NOT IN ('waiting_for_section_2_verification','section_2_verification_done','employee_athorized','verified')
                THEN 1 END) as waiting_for_approval_count,

            COUNT(CASE 
                WHEN tnc = 0 
                AND status = 'waiting_for_section_2_verification'
                THEN 1 END) as waiting_for_section2_verification_count,

            COUNT(CASE 
                WHEN tnc = 0 
                AND status = 'section_2_verification_done'
                THEN 1 END) as waiting_for_employee_authorized_count,

                
                COUNT(CASE 
                WHEN tnc = 1 
                THEN 1 END) as tnc_count
                ")->first();
        // COUNT(CASE 
        //     WHEN tnc = 0 
        //     AND status = 'employee_athorized'
        //     THEN 1 END) as waiting_for_internal_review_count,

        return response()->json($stats);
    }

    public function workbrightInProgressData(Request $request)
    {
        return $this->workbrightBucketData($request, function ($query) {
            $query->where('tnc', 0)
                ->where('process_id', 10)
                ->whereNotIn('status', ['waiting_for_section_2_verification', 'section_2_verification_done', 'employee_athorized', 'verified']);
        });
    }

    public function workbrightWaitingForApprovalData(Request $request)
    {
        return $this->workbrightBucketData($request, function ($query) {
            $query->where('tnc', 0)
                ->where('i9_completed', 1)
                ->where('w4_completed', 1)
                ->whereNotIn('status', ['i9_submitted', 'waiting_for_section_2_verification', 'section_2_verification_done', 'employee_athorized', 'verified']);
        });
    }

    public function workbrightWaitingForSection2VerificationData(Request $request)
    {
        return $this->workbrightBucketData($request, function ($query) {
            $query->where('tnc', 0)
                ->where('status', 'waiting_for_section_2_verification');
        });
    }

    public function workbrightWaitingForEmployeeAuthorizedData(Request $request)
    {
        return $this->workbrightBucketData($request, function ($query) {
            $query->where('tnc', 0)
                ->where('status', 'section_2_verification_done');
        });
    }

    public function authorizeWorkbrightEmployees(Request $request)
    {
        $this->authorize('access', 'workbright-dashboard.show');

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $records = OnboardingList::query()
            ->authorizedCompanies('company_id')
            ->where('tnc', 0)
            ->where('status', 'section_2_verification_done')
            ->whereIn('id', $request->input('ids'))
            ->get();

        if ($records->isEmpty()) {
            return to_json([
                'success' => false,
                'message' => 'No eligible employees found to authorize.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $logTime = now();
            foreach ($records as $onboarding) {
                $previousStatus = $onboarding->status;
                $onboarding->status = 'employee_athorized';
                $onboarding->save();

                Employee::where('id', $onboarding->employee_id)->update([
                    'onboarding_status' => 'employee_athorized',
                ]);

                OnboardingLogs::create([
                    'onboarding_list_id' => $onboarding->id,
                    'event' => 'employee_authorized',
                    'data' => [
                        'action' => 'authorize',
                        'previous_status' => $previousStatus,
                        'updated_status' => $onboarding->status,
                        'updated_by' => Auth::id(),
                    ],
                    'description' => 'Employee authorized from WorkBright dashboard',
                    'date' => $logTime,
                ]);
            }

            DB::commit();

            $count = $records->count();

            return to_json([
                'success' => true,
                'message' => $count === 1
                    ? 'Employee authorized successfully.'
                    : "{$count} employees authorized successfully.",
                'authorized_count' => $count,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('WorkBright employee authorize error: ' . $e->getMessage());

            return to_json([
                'success' => false,
                'message' => 'Failed to authorize employees.',
            ], 500);
        }
    }

    public function workbrightWaitingForInternalReviewData(Request $request)
    {
        return $this->workbrightBucketData($request, function ($query) {
            $query->where('tnc', 0)
                ->where('status', 'employee_authorized');
        });
    }

    public function workbrightTncData(Request $request)
    {
        return $this->workbrightBucketData($request, function ($query) {
            $query->where('tnc', 1);
        });
    }


    /**
     * Get year-wise bi-weekly payroll total earnings for chart.
     */
    public function biWeeklyPayrollChart(Request $request)
    {
        $this->authorize('access', 'payroll-report.index');

        $year = (int) ($request->query('year') ?? Carbon::now()->year);
        $authorizedCompanies = authorizedCompanies();
        $companyIds = $request->query('company_ids');
        if (is_string($companyIds)) {
            $companyIds = array_filter(array_map('intval', explode(',', $companyIds)));
        } elseif (is_array($companyIds)) {
            $companyIds = array_filter(array_map('intval', $companyIds));
        } else {
            $companyIds = [];
        }
        $companyIds = array_values(array_intersect($companyIds, $authorizedCompanies));

        $yearStart = Carbon::parse($year . '-01-01');
        $dayOfWeek = (int) $yearStart->format('N');
        $daysToMonday = $dayOfWeek === 7 ? -6 : 1 - $dayOfWeek - 7;
        $firstMonday = $yearStart->copy()->addDays($daysToMonday);

        $eows = [];
        for ($i = 0; $i < 26; $i++) {
            $eow = $firstMonday->copy()->addDays(($i * 14) + 13);
            if ($eow->year == $year || ($eow->year == $year + 1 && $eow->month == 1)) {
                $eows[] = $eow->format('Y-m-d');
            }
        }

        $startDate = Carbon::parse($eows[0])->subDays(13);
        $endDate = Carbon::parse($eows[count($eows) - 1]);

        $weeklySummaries = EmployeeWeeklySummary::whereBetween('eow', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->groupBy('eow')
            ->authorizedCompanies('company_id')
            ->when(! empty($companyIds), fn($q) => $q->whereIn('company_id', $companyIds))
            ->selectRaw('eow, sum(total_earnings) as total_earnings')
            ->get()
            ->keyBy('eow');

        $labels = [];
        $series = [];

        foreach ($eows as $eow) {
            $firstEow = Carbon::parse($eow)->subDays(7)->format('Y-m-d');
            $summariesWeek1 = $weeklySummaries[$firstEow] ?? null;
            $summariesWeek2 = $weeklySummaries[$eow] ?? null;
            $totalEarnings = ((float) ($summariesWeek1?->total_earnings ?? 0)) + ((float) ($summariesWeek2?->total_earnings ?? 0));

            $labels[] = Carbon::parse($eow)->format('m/d');
            $series[] = round((float) $totalEarnings, 2);
        }

        return response()->json([
            'year' => $year,
            'labels' => $labels,
            'series' => $series,
        ]);
    }

    public function workbrightData(Request $request)
    {
        $this->authorize('access', 'workbright-dashboard.show');


        $page = $request->query('page') ?? 1;
        $limit = $request->query('limit') ?? 25;
        $search = $request->query('search') ?? '';


        if (session('workgroup')) {
            $employees = OnboardingList::whereNot('status', 'verified')
                ->authorizedCompanies('company_id')
                ->when($search, function ($query) use ($search) {
                    $query->whereHas('employee', function ($query) use ($search) {
                        $query->where('pos_name', 'like', '%' . $search . '%')->orWhere('employee_id', 'like', '%' . $search . '%');
                    })->orWhere('onboarding_number', 'like', '%' . $search . '%');
                })
                ->with('employee:id,pos_name')
                ->paginate($limit, ['*'], 'page', $page);
        } else {
            $employees = [];
        }
        return to_json([
            'success' => true,
            'data' => $employees->items(),
            'pagination' => [
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'from' => $employees->firstItem(),
                'to' => $employees->lastItem(),
                'total' => $employees->total(),
                'per_page' => $employees->perPage(),
                'has_prev' => $employees->onFirstPage() ? false : true,
                'has_next' => $employees->hasMorePages(),
            ]
        ], 200);
    }

    private function workbrightBucketData(Request $request, callable $bucketFilter)
    {
        $this->authorize('access', 'workbright-dashboard.show');

        $page = max((int) ($request->query('page') ?? 1), 1);
        $limit = min(max((int) ($request->query('limit') ?? 25), 1), 100);
        $search = trim((string) ($request->query('search') ?? ''));

        if (!session('workgroup')) {
            return to_json([
                'success' => true,
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'from' => 0,
                    'to' => 0,
                    'total' => 0,
                    'per_page' => $limit,
                    'has_prev' => false,
                    'has_next' => false,
                ],
            ], 200);
        }

        $query = OnboardingList::query()
            ->authorizedCompanies('company_id');

        $bucketFilter($query);

        $employees = $query
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('employee', function ($employeeQuery) use ($search) {
                        $employeeQuery->where('pos_name', 'like', '%' . $search . '%')
                            ->orWhere('employee_id', 'like', '%' . $search . '%');
                    })->orWhere('onboarding_number', 'like', '%' . $search . '%');
                });
            })
            ->with('employee:id,pos_name,employee_id')
            ->orderByDesc('updated_at')
            ->paginate($limit, ['*'], 'page', $page);

        return to_json([
            'success' => true,
            'data' => $employees->items(),
            'pagination' => [
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'from' => $employees->firstItem(),
                'to' => $employees->lastItem(),
                'total' => $employees->total(),
                'per_page' => $employees->perPage(),
                'has_prev' => ! $employees->onFirstPage(),
                'has_next' => $employees->hasMorePages(),
            ],
        ], 200);
    }

    public function workbrightCompletedData(Request $request)
    {
        $this->authorize('access', 'workbright-dashboard.show');

        $page = $request->query('page') ?? 1;
        $limit = $request->query('limit') ?? 25;
        $search = $request->query('search') ?? '';

        if (session('workgroup')) {
            $employees = OnboardingList::where('i9_approved', true)->where('status', 'verified')
                ->authorizedCompanies('company_id')
                ->when($search, function ($query) use ($search) {
                    $query->whereHas('employee', function ($query) use ($search) {
                        $query->where('pos_name', 'like', '%' . $search . '%')
                            ->orWhere('employee_id', 'like', '%' . $search . '%');
                    })->orWhere('onboarding_number', 'like', '%' . $search . '%');
                })
                ->with('employee:id,pos_name')
                ->paginate($limit, ['*'], 'page', $page);
        } else {
            $employees = [];
        }
        return to_json([
            'success' => true,
            'data' => $employees->items(),
            'pagination' => [
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'from' => $employees->firstItem(),
                'to' => $employees->lastItem(),
                'total' => $employees->total(),
                'per_page' => $employees->perPage(),
                'has_prev' => $employees->onFirstPage() ? false : true,
                'has_next' => $employees->hasMorePages(),
            ]
        ], 200);
    }

    public function tncEmployees(Request $request)
    {
        $this->authorize('access', 'employee.show');

        $page = max((int) ($request->query('page') ?? 1), 1);
        $limit = min(max((int) ($request->query('limit') ?? 25), 1), 100);
        $search = trim((string) ($request->query('search') ?? ''));

        if (session('workgroup')) {
            $employees = Employee::query()
                ->where('tnc', true)
                ->authorizedCompanies('company_id')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('pos_name', 'like', '%' . $search . '%')
                            ->orWhere('employee_id', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    });
                })
                ->with('company:id,name,store_number')
                ->orderBy('pos_name')
                ->paginate($limit, ['id', 'employee_id', 'pos_name', 'email', 'company_id', 'tnc'], 'page', $page);
        } else {
            $employees = [];
        }
        return to_json([
            'success' => true,
            'data' => $employees->items(),
            'pagination' => [
                'current_page' => $employees->currentPage(),
                'last_page' => $employees->lastPage(),
                'from' => $employees->firstItem(),
                'to' => $employees->lastItem(),
                'total' => $employees->total(),
                'per_page' => $employees->perPage(),
                'has_prev' => $employees->onFirstPage() ? false : true,
                'has_next' => $employees->hasMorePages(),
            ],
        ], 200);
    }

    public function workbrightExport(Request $request)
    {
        $this->authorize('access', 'workbright-dashboard.show');

        $search = (string) ($request->query('search') ?? '');

        if (session('workgroup')) {
            $employees = OnboardingList::whereIn('status', ['form_submitted', 'i9_submission_requested', 'w4_submission_requested', 'i9_and_w4_submission_requested'])
                ->authorizedCompanies('company_id')
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->whereHas('employee', function ($q2) use ($search) {
                            $q2->where('pos_name', 'like', '%' . $search . '%')
                                ->orWhere('employee_id', 'like', '%' . $search . '%');
                        })->orWhere('onboarding_number', 'like', '%' . $search . '%');
                    });
                })
                ->with('employee:id,pos_name,employee_id')
                ->orderByDesc('updated_at')
                ->get();
        } else {
            $employees = [];
        }
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'Employee',
            'Employee ID',
            'Onboarding #',
            'Status',
            'Final Status',
            'Created At',
            'Updated At',
        ];

        $headerRange = 'A1:G1';
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle($headerRange)->getFont()->getColor()->setARGB('FFFFFFFF');

        $rowNumber = 2;
        foreach ($employees as $item) {
            $sheet->fromArray([
                $item->employee?->pos_name ?? '',
                $item->employee?->employee_id ?? '',
                $item->onboarding_number ?? '',
                $item->status ?? '',
                $item->final_status ?? '',
                $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : '',
                $item->updated_at ? $item->updated_at->format('Y-m-d H:i:s') : '',
            ], null, 'A' . $rowNumber);
            $rowNumber++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'workbright_onboarding_export_' . date('Y-m-d_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    public function workbrightCompletedExport(Request $request)
    {
        $this->authorize('access', 'workbright-dashboard.show');

        $search = (string) ($request->query('search') ?? '');

        if (session('workgroup')) {
            $employees = OnboardingList::where('status', 'verified')
                ->authorizedCompanies('company_id')
                ->when($search, function ($query) use ($search) {
                    $query->where(function ($q) use ($search) {
                        $q->whereHas('employee', function ($q2) use ($search) {
                            $q2->where('pos_name', 'like', '%' . $search . '%')
                                ->orWhere('employee_id', 'like', '%' . $search . '%');
                        })->orWhere('onboarding_number', 'like', '%' . $search . '%');
                    });
                })
                ->with('employee:id,pos_name,employee_id')
                ->orderByDesc('updated_at')
                ->get();
        } else {
            $employees = [];
        }
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'Employee',
            'Employee ID',
            'Onboarding #',
            'Status',
            'Final Status',
            'Created At',
            'Updated At',
        ];

        $headerRange = 'A1:G1';
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle($headerRange)->getFont()->getColor()->setARGB('FFFFFFFF');

        $rowNumber = 2;
        foreach ($employees as $item) {
            $sheet->fromArray([
                $item->employee?->pos_name ?? '',
                $item->employee?->employee_id ?? '',
                $item->onboarding_number ?? '',
                $item->status ?? '',
                $item->final_status ?? '',
                $item->created_at ? $item->created_at->format('Y-m-d H:i:s') : '',
                $item->updated_at ? $item->updated_at->format('Y-m-d H:i:s') : '',
            ], null, 'A' . $rowNumber);
            $rowNumber++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'workbright_completed_export_' . date('Y-m-d_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Onboarding activity logs for authorized companies (latest first).
     */
    public function workbrightLogs(Request $request)
    {
        $this->authorize('access', 'workbright-dashboard.show');

        $page = max((int) ($request->query('page') ?? 1), 1);
        $perPage = min(max((int) ($request->query('limit') ?? 25), 1), 100);
        $onboardingListId = $request->query('onboarding_list_id');

        if (session('workgroup')) {
            $logs = OnboardingLogs::query()
                ->whereHas('onboardingList', function ($q) use ($onboardingListId) {
                    $q->authorizedCompanies('company_id');
                    if ($onboardingListId !== null && $onboardingListId !== '') {
                        $q->where('id', (int) $onboardingListId);
                    }
                })
                ->with([
                    'onboardingList' => function ($q) {
                        $q->select('id', 'employee_id', 'onboarding_number', 'company_id')
                            ->with('employee:id,pos_name,employee_id');
                    },
                ])
                ->orderByDesc('id')
                ->paginate($perPage, ['*'], 'page', $page);
        } else {
            $logs = [];
        }
        $items = collect($logs->items())->map(function (OnboardingLogs $log) {
            return [
                'id' => $log->id,
                'event' => $log->event,
                'description' => $log->description,
                'data' => $log->data,
                'date' => $log->date?->format('Y-m-d H:i:s'),
                'created_at' => $log->created_at?->format('Y-m-d H:i:s'),
                'onboarding_number' => $log->onboardingList?->onboarding_number,
                'employee_name' => $log->onboardingList?->employee?->pos_name,
                'employee_id' => $log->onboardingList?->employee?->employee_id,
            ];
        })->all();

        return to_json([
            'success' => true,
            'data' => $items,
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'from' => $logs->firstItem(),
                'to' => $logs->lastItem(),
                'total' => $logs->total(),
                'per_page' => $logs->perPage(),
                'has_prev' => $logs->onFirstPage() ? false : true,
                'has_next' => $logs->hasMorePages(),
            ],
        ], 200);
    }

    /**
     * Export onboarding activity logs to Excel (same scope as workbrightLogs).
     */
    public function workbrightLogsExport(Request $request)
    {
        $this->authorize('access', 'workbright-dashboard.show');

        $onboardingListId = $request->query('onboarding_list_id');

        if (session('workgroup')) {
            $logs = OnboardingLogs::query()
                ->whereHas('onboardingList', function ($q) use ($onboardingListId) {
                    $q->authorizedCompanies('company_id');
                    if ($onboardingListId !== null && $onboardingListId !== '') {
                        $q->where('id', (int) $onboardingListId);
                    }
                })
                ->with([
                    'onboardingList' => function ($q) {
                        $q->select('id', 'employee_id', 'onboarding_number', 'company_id')
                            ->with('employee:id,pos_name,employee_id');
                    },
                ])
                ->orderByDesc('id')
                ->get();
        } else {
            $logs = [];
        }
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'Log ID',
            'When',
            'Employee ID',
            'Employee',
            'Onboarding #',
            'Event',
            'Description',
        ];

        $headerRange = 'A1:G1';
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle($headerRange)->getFont()->getColor()->setARGB('FFFFFFFF');

        $rowNumber = 2;
        foreach ($logs as $log) {
            $when = $log->date ?? $log->created_at;
            $sheet->fromArray([
                $log->id,
                $when ? $when->format('Y-m-d H:i:s') : '',
                $log->onboardingList?->employee?->employee_id ?? '',
                $log->onboardingList?->employee?->pos_name ?? '',
                $log->onboardingList?->onboarding_number ?? '',
                $log->event ?? '',
                $log->description ?? '',
            ], null, 'A' . $rowNumber);
            $rowNumber++;
        }

        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'workbright_onboarding_logs_' . date('Y-m-d_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    /**
     * Refresh WorkBright status by triggering the sync command.
     */
    public function refreshWorkbrightStatus(Request $request)
    {
        $this->authorize('access', 'workbright-dashboard.show');

        $lockKey = 'workbright:sync-status:running';

        if (Cache::has($lockKey)) {
            return to_json([
                'success' => false,
                'message' => 'A WorkBright status sync is already in progress. Please wait for it to finish.',
            ], 409);
        }

        try {
            Cache::put($lockKey, true, now()->addMinutes(30));

            dispatch(function () use ($lockKey) {
                try {
                    Artisan::call('workbright:sync-status');
                } catch (\Exception $e) {
                    Log::error('WorkBright status refresh error: ' . $e->getMessage());
                } finally {
                    Cache::forget($lockKey);
                }
            })->afterResponse();

            return to_json([
                'success' => true,
                'message' => 'WorkBright status sync started. Dashboard data will refresh shortly.',
            ], 200);
        } catch (\Exception $e) {
            Cache::forget($lockKey);
            Log::error('WorkBright status refresh error: ' . $e->getMessage());

            return to_json([
                'success' => false,
                'message' => 'Failed to start WorkBright status sync: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function fundRequirement(Request $request)
    {
        $this->authorize('access', 'fund-requirement.show');
        $days = 10;

        $foodPurchaseDueDays = getSettingValue('food-purchase-due-(days)', 6);
        // $royaltyPaymentDay = getSettingValue('royalty-payment-day', 10);
        // $advertisementPaymentDay = getSettingValue('advertisement-payment-day', 24);
        // $royaltyPercentage = getSettingValue('royalty-percentage', 5);
        // $advertisementPercentage = getSettingValue('advertisement-percentage', 7);


        $today = now();
        // $royaltyPaymentDate = Carbon::createFromDate($today->year, $today->month, $royaltyPaymentDay)->format('Y-m-d');
        // $advertisementPaymentDate = Carbon::createFromDate($today->year, $today->month, $advertisementPaymentDay)->format('Y-m-d');





        $tenDays = [];
        $targetDates = [];

        for ($i = -5; $i < $days; $i++) {
            $currentDate = $today->copy()->addDays($i);
            $dateStr = $currentDate->format('Y-m-d');
            $targetDateStr = $currentDate->copy()->subDays($foodPurchaseDueDays + 1)->format('Y-m-d');

            $day = $currentDate->format('l');
            $arr = [
                'date' => $dateStr,
                'day' => $day,
                'food_purchase' => 0,
                'royalty_payment' => 0,
                'advertisement_payment' => 0,
                'month' => $currentDate->month,
            ];
            if ($day == 'Monday') {
                $arr['target_date'] = $currentDate->copy()->subDays($foodPurchaseDueDays + 3)->format('Y-m-d');
            } else if ($day == 'Tuesday') {
                $arr['target_date'] = [
                    $targetDateStr,
                    $currentDate->copy()->subDays($foodPurchaseDueDays + 2)->format('Y-m-d'),
                    $currentDate->copy()->subDays($foodPurchaseDueDays + 3)->format('Y-m-d')
                ];
            } else if ($day == 'Saturday' || $day == 'Sunday') {
                $arr['target_date'] = null;
            } else {
                $arr['target_date'] = $targetDateStr;
            }
            if (is_array($arr['target_date'])) {
                $targetDates = array_merge($targetDates, $arr['target_date']);
            } else {
                $targetDates[] = $arr['target_date'];
            }
            $tenDays[$dateStr] = $arr;
        }
        $labels = [];

        $fundRequirements = FundRequirement::where('active', true)->orderBy('order', 'asc')->get();
        $uniqueMonthsFromTenDays = array_unique(array_column($tenDays, 'month'));
        foreach ($fundRequirements as $fundRequirement) {
            $label = $fundRequirement->label;
            $labels[] = $label;
            if ($fundRequirement->condition_type == 'monthly') {
                $date = Carbon::parse($fundRequirement->condition_value)->format('d');
                foreach ($uniqueMonthsFromTenDays as $month) {
                    $monthDate = Carbon::createFromDate($today->year, $month, $date)->format('Y-m-d');
                    $paymentDates = $this->getPaymentDates([$monthDate], $tenDays);
                    foreach ($paymentDates as $paymentDate) {
                        if (isset($tenDays[$paymentDate])) {
                            $tenDays[$paymentDate][$label] = $fundRequirement->amount;
                        }
                    }
                }
            } else if ($fundRequirement->condition_type == 'weekly') {
                $dates = $this->getPayrollDates($fundRequirement->condition_value, $days, 'weekly');
                $paymentDates = $this->getPaymentDates($dates, $tenDays);
                foreach ($paymentDates as $paymentDate) {
                    if (isset($tenDays[$paymentDate])) {
                        $tenDays[$paymentDate][$label] = $fundRequirement->amount;
                    }
                }
            } else if ($fundRequirement->condition_type == 'bi-weekly') {
                $dates = $this->getPayrollDates($fundRequirement->condition_value, $days, 'bi-weekly');
                $paymentDates = $this->getPaymentDates($dates, $tenDays);
                foreach ($paymentDates as $paymentDate) {
                    if (isset($tenDays[$paymentDate])) {
                        $tenDays[$paymentDate][$label] = $fundRequirement->amount;
                    }
                }
            }
        }

        // $royaltyDate = null;
        // $advertisementDate = null;
        // foreach ($uniqueMonthsFromTenDays as $month) {
        //     $royaltyPaymentDate = Carbon::createFromDate($today->year, $month, $royaltyPaymentDay)->format('Y-m-d');
        //     $advertisementPaymentDate = Carbon::createFromDate($today->year, $month, $advertisementPaymentDay)->format('Y-m-d');
        //     $rDate = $this->getPaymentDate($royaltyPaymentDate, $tenDays, 'sales');
        //     $aDate = $this->getPaymentDate($advertisementPaymentDate, $tenDays, 'sales');
        //     if (isset($tenDays[$rDate])) {
        //         $royaltyDate = $rDate;
        //     }
        //     if (isset($tenDays[$aDate])) {
        //         $advertisementDate = $aDate;
        //     }
        // }

        // if ($royaltyDate || $advertisementDate) {
        //     $lastMonthStartWeek = Carbon::createFromDate($today->year, $today->month - 1, 1)
        //         ->startOfWeek()
        //         ->format('Y-m-d');
        //     $lastSunday = $today->copy()->subMonth()->endOfMonth()->previous(Carbon::SUNDAY)->format('Y-m-d');

        //     $dailySales = DailySale::whereBetween('date', [$lastMonthStartWeek, $lastSunday])
        //         ->selectRaw('SUM(net_sales) as total_sales')
        //         ->first();

        //     if ($dailySales && $dailySales->total_sales) {
        //         if ($royaltyDate) {
        //             $tenDays[$royaltyDate]['royalty_payment'] = $dailySales->total_sales * $royaltyPercentage / 100;
        //             $tenDays[$royaltyDate]['total_sales'] = $dailySales->total_sales;
        //             $tenDays[$royaltyDate]['royalty_percentage'] = $royaltyPercentage;
        //         }

        //         if ($advertisementDate) {
        //             $tenDays[$advertisementDate]['advertisement_payment'] = $dailySales->total_sales * $advertisementPercentage / 100;
        //             $tenDays[$advertisementDate]['total_sales'] = $dailySales->total_sales;
        //             $tenDays[$advertisementDate]['advertisement_percentage'] = $advertisementPercentage;
        //         }
        //     }
        // }

        $royaltyFees = RoyaltyFee::query()
            ->whereIn('due_date', array_keys($tenDays))
            ->selectRaw('due_date, type, SUM(amount) as total_amount')
            ->groupBy('due_date', 'type')
            ->get();

        foreach ($royaltyFees as $fee) {
            $dueDate = Carbon::parse($fee->due_date)->format('Y-m-d');
            $paymentDate = $this->getPaymentDate($dueDate, $tenDays, 'sales');
            if (!$paymentDate || !isset($tenDays[$paymentDate])) {
                continue;
            }

            $amount = (float) $fee->total_amount;
            if ($fee->type === RoyaltyFee::TYPE_ROYALTY) {
                $tenDays[$paymentDate]['royalty_payment'] += $amount;
            } elseif ($fee->type === RoyaltyFee::TYPE_ADVERTISEMENT) {
                $tenDays[$paymentDate]['advertisement_payment'] += $amount;
            }
        }


        $foodPurchases = FoodPurchase::whereIn('date', array_unique($targetDates))
            ->selectRaw('date, SUM(total_amount) as total_amount')
            ->groupBy('date')
            ->pluck('total_amount', 'date');

        foreach ($tenDays as $index => $day) {
            if (is_array($day['target_date'])) {
                $totalAmount = 0;
                $tenDays[$index]['target_date'] = [];
                foreach ($day['target_date'] as $date) {
                    $tenDays[$index]['target_date'][] = [
                        'date' => $date,
                        'amount' => $foodPurchases[$date] ?? 0,
                    ];
                    $totalAmount += $foodPurchases[$date] ?? 0;
                }
                $tenDays[$index]['food_purchase'] = $totalAmount;
            } else if ($day['target_date'] !== null) {
                $tenDays[$index]['food_purchase'] = $foodPurchases[$day['target_date']] ?? 0;
            }
        }

        return to_json([
            'data' => array_slice(array_values($tenDays), 3),
            'labels' => $labels,
        ]);
    }
    private function getPayrollDates($startDate, $days, $type)
    {
        $dates = [];
        $lastDate = now()->addDays($days);
        $currentDate = Carbon::parse($startDate);

        while ($currentDate->isBefore($lastDate)) {
            $dates[] = $currentDate->format('Y-m-d');
            $currentDate = $currentDate->addDays($type == 'weekly' ? 7 : 14);
        }
        return $dates;
    }
    private function getPaymentDates($payrollDates, $tenDays)
    {
        $dates = [];
        foreach ($payrollDates as $payrollDate) {
            if (isset($tenDays[$payrollDate])) {
                $dates[] = $this->getPaymentDate($payrollDate, $tenDays);
            }
        }
        return $dates;
    }
    private function getPaymentDate($paymentDate, $tenDays, $type = '')
    {
        if (!isset($tenDays[$paymentDate])) {
            return null;
        }

        $day = $tenDays[$paymentDate];
        if ((($type != 'sales' && $day['day'] == 'Friday') || $day['day'] != 'Friday') && $day['day'] != 'Saturday' && $day['day'] != 'Sunday') {
            return $paymentDate;
        }

        foreach ($tenDays as $date => $dayData) {
            if ($dayData['day'] == 'Monday' && Carbon::parse($date)->isAfter($paymentDate)) {
                return $date;
            }
        }

        return null;
    }
}
