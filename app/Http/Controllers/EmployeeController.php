<?php

namespace App\Http\Controllers;

use App\Models\DataEntry\Drivers;
use App\Models\Employee;
use App\Models\Onboarding\EmployeeDocument;
use App\Models\Onboarding\EmployeeHoursRequest;
use App\Models\Payroll\EmployeeHours;
use App\Models\Payroll\EmployeeRates;
use App\Models\Onboarding\EmployeeRateRequest;
use App\Models\Onboarding\OnboardingList;
use App\Models\Payroll\EmployeeInactive;
use App\Models\Payroll\EmployeeWeeklySummary;
use App\Models\Payroll\PayrollCheckAmount;
use App\Models\Settings\CheckMaster;
use App\Models\Settings\Company;
use App\Models\Settings\EmployeeRoles;
use App\Services\FileUploadService;
use App\Services\EmployeeRateRequestNotificationService;
use App\Services\NewHireNotificationService;
use App\Services\WeeklySummaryRecalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


class EmployeeController extends Controller
{
    public $skipOnboarding;
    public function __construct()
    {
        $this->skipOnboarding = config('app.skip_onboarding');
    }
    public function index(Request $request)
    {
        $context = $this->prepareEmployeeListRequest($request);
        $employees = $this->buildEmployeeListQuery($context['employeeType'], $context['companyIds'])
            ->with('company', 'employeeRates', 'employeeRatesRequests', 'employeeDocuments', 'onboardingList:id,employee_id,i9_completed,w4_completed,i9_approved,w4_approved,i9_rejected,w4_rejected,work_bright_employee_id', 'aliases')
            ->filter();

        return to_json([
            'collection' => $employees,
        ]);
    }

    public function export(Request $request)
    {
        $this->authorize('access', 'employee.index');

        $context = $this->prepareEmployeeListRequest($request);
        $employeeType = $context['employeeType'];

        $with = ['employeeRates.company', 'employeeRatesRequests.company', 'aliases'];
        if ($employeeType === 'PendingI9W4') {
            $with[] = 'employeeDocuments';
            $with['onboardingList'] = fn($q) => $q->select(
                'id',
                'employee_id',
                'i9_completed',
                'w4_completed',
                'i9_approved',
                'w4_approved',
                'i9_rejected',
                'w4_rejected'
            );
        } elseif ($employeeType === 'MissingProfilePicture') {
            $with[] = 'employeeDocuments';
        }

        $employees = $this->buildEmployeeListQuery($employeeType, $context['companyIds'])
            ->with($with)
            ->export($request->all());

        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = $this->getEmployeeExportHeaders($employeeType);
        $lastCol = Coordinate::stringFromColumnIndex(count($headers));

        $sheet->fromArray($headers, null, 'A1');
        $headerRange = 'A1:' . $lastCol . '1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF4472C4');
        $sheet->getStyle($headerRange)->getFont()->getColor()->setARGB('FFFFFFFF');

        $row = 2;
        foreach ($employees as $employee) {
            $sheet->fromArray(
                $this->getEmployeeExportRow($employee, $employeeType),
                null,
                'A' . $row
            );
            $row++;
        }

        for ($i = 1; $i <= count($headers); $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'employees_export_' . date('Y-m-d_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    private function getEmployeeExportHeaders(string $employeeType): array
    {
        $headers = [
            'Company Names',
            'Employee ID',
            'POS Name',
            'Alias Employee IDs',
            'Alias Names',
            'Email',
            'Phone Number',
        ];

        if ($employeeType === 'PendingI9W4') {
            $headers = array_merge($headers, [
                'Onboarding Status',
                'I-9 (Workbright)',
                'W-4 (Workbright)',
                'I-9',
                'W-4',
                'Hire Date',
                'Created At',
                'Documents Count',
            ]);
        } elseif ($employeeType === 'MissingProfilePicture') {
            $headers = array_merge($headers, [
                'Profile Photo',
                'Hire Date',
                'Created At',
            ]);
        }

        return $headers;
    }

    private function getEmployeeExportRow(Employee $employee, string $employeeType): array
    {
        $row = [
            $this->formatEmployeeCompanyNames($employee),
            $employee->employee_id ?? '',
            $employee->pos_name ?? '',
            collect($employee->aliasIds())->implode(', '),
            collect($employee->aliasNames())->implode(', '),
            $employee->email ?? '',
            $employee->phone ?? '',
        ];

        if ($employeeType === 'PendingI9W4') {
            $onboardingList = $employee->onboardingList;
            $row = array_merge($row, [
                $this->formatOnboardingStatus($employee->onboarding_status),
                $this->formatWorkbrightI9Status($onboardingList),
                $this->formatWorkbrightW4Status($onboardingList),
                $this->formatEmployeeDocumentStatus($employee, EmployeeDocument::DOCUMENT_TYPE_I9_FORM),
                $this->formatEmployeeDocumentStatus($employee, EmployeeDocument::DOCUMENT_TYPE_W4_FORM),
                $this->formatExportDate($employee->hire_date),
                $this->formatExportDate($employee->created_at),
                $employee->employeeDocuments?->count() ?? 0,
            ]);
        } elseif ($employeeType === 'MissingProfilePicture') {
            $row = array_merge($row, [
                $this->formatProfilePhotoStatus($employee),
                $this->formatExportDate($employee->hire_date),
                $this->formatExportDate($employee->created_at),
            ]);
        }

        return $row;
    }

    private function formatOnboardingStatus(?string $status): string
    {
        if ($status === null || $status === '') {
            return '';
        }

        $label = str_replace('_', ' ', $status);

        return ucfirst($label);
    }

    private function formatWorkbrightI9Status(?OnboardingList $onboardingList): string
    {
        if (!$onboardingList) {
            return 'Pending';
        }
        if ($onboardingList->i9_rejected) {
            return 'Rejected';
        }
        if ($onboardingList->i9_approved) {
            return 'Approved';
        }
        if ($onboardingList->i9_completed) {
            return 'Completed';
        }

        return 'Pending';
    }

    private function formatWorkbrightW4Status(?OnboardingList $onboardingList): string
    {
        if (!$onboardingList) {
            return 'Pending';
        }
        if ($onboardingList->w4_rejected) {
            return 'Rejected';
        }
        if ($onboardingList->w4_approved) {
            return 'Approved';
        }
        if ($onboardingList->w4_completed) {
            return 'Completed';
        }

        return 'Pending';
    }

    private function formatEmployeeDocumentStatus(Employee $employee, string $documentType): string
    {
        $hasDocument = $employee->employeeDocuments
            ?->contains(fn($doc) => $doc->document_type === $documentType) ?? false;

        return $hasDocument ? 'Completed' : 'Pending';
    }

    private function formatProfilePhotoStatus(Employee $employee): string
    {
        $pic = $employee->profile_picture;
        if ($pic !== null && trim((string) $pic) !== '') {
            return 'Completed';
        }

        $hasDocument = $employee->employeeDocuments
            ?->contains(fn($doc) => $doc->document_type === EmployeeDocument::DOCUMENT_TYPE_PROFILE_PICTURE) ?? false;

        return $hasDocument ? 'Completed' : 'Pending';
    }

    private function formatExportDate($date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        return Carbon::parse($date)->format('M j, Y');
    }

    /**
     * @return array{employeeType: string, companyIds: array<int, string>|null}
     */
    private function prepareEmployeeListRequest(Request $request): array
    {
        $employeeType = $request->query('employee_type') ?? 'Completed';
        $companyIds = null;

        if ($request->has('f') && is_array($request->input('f'))) {
            $filters = $request->input('f');
            $filteredFilters = [];

            foreach ($filters as $filter) {
                if (isset($filter['column']) && $filter['column'] === 'company_id') {
                    if ($filter['operator'] === 'includes' && isset($filter['query_1'])) {
                        $companyIds = explode(',,', $filter['query_1']);
                    }
                    continue;
                }
                $filteredFilters[] = $filter;
            }

            if (empty($filteredFilters)) {
                $requestData = $request->all();
                unset($requestData['f']);
                $request->replace($requestData);
            } else {
                $request->merge(['f' => $filteredFilters]);
            }
        }

        return [
            'employeeType' => $employeeType,
            'companyIds' => $companyIds,
        ];
    }

    private function buildEmployeeListQuery(string $employeeType, ?array $companyIds)
    {
        $payrollOnboardedScope = function ($q) {
            $q->where(function ($q) {
                $q->whereHas('employeeRates', function ($q) {
                    $q->whereIn('rate_type', ['Payroll 1099', 'Payroll Regular', 'Payroll Slab']);
                })->orWhereHas('employeeRatesRequests', function ($q) {
                    $q->whereIn('rate_type', ['Payroll 1099', 'Payroll Regular', 'Payroll Slab']);
                });
            });
        };
        $onboarded1099Scope = function ($q) {
            $q->where(function ($q) {
                $q->whereHas('employeeRates', function ($q) {
                    $q->whereIn('rate_type', ['1099 Regular', '1099 Slab', '1099 1099']);
                })->orWhereHas('employeeRatesRequests', function ($q) {
                    $q->whereIn('rate_type', ['1099 Regular', '1099 Slab', '1099 1099']);
                });
            });
        };

        return Employee::query()
            ->where(function ($q) use ($employeeType, $payrollOnboardedScope, $onboarded1099Scope) {
                if ($employeeType === 'Completed') {
                    $q->where('employee_type', 'Completed')->orWhereHas('employeeRates')
                    // ->orWhereHas('employeeRatesRequests', function ($q) {
                    //     $q->authorizedCompanies('company_id');
                    // })
                    ->orWhere(function ($q) {
                        $q->whereDoesntHave('employeeRates')
                            ->whereDoesntHave('employeeRatesRequests');
                    });
                } elseif ($employeeType === 'New') {
                    $q->where('employee_type', 'New')->where('active', true)->where(function ($q2) use ($payrollOnboardedScope) {
                        $q2->where(function ($q3) use ($payrollOnboardedScope) {
                            $payrollOnboardedScope($q3);
                            $q3->where(function ($q4) {
                                $q4->whereDoesntHave('onboardingList')->orWhere('mail_sent', 0);
                            });
                        })->orWhere('rejected', true);
                    });
                } elseif ($employeeType === 'PendingI9W4') {
                    $q->where('active', true)->where(function ($q2) {
                        $q2->whereDoesntHave('employeeDocuments', function ($d) {
                            $d->where('document_type', EmployeeDocument::DOCUMENT_TYPE_I9_FORM);
                        })->orWhereDoesntHave('employeeDocuments', function ($d) {
                            $d->where('document_type', EmployeeDocument::DOCUMENT_TYPE_W4_FORM);
                        });
                    })
                        ->where('i9_doc_skip', false)
                        ->whereHas('onboardingList', function ($q) {
                            $q->whereNotNull('work_bright_employee_id');
                        });
                    $payrollOnboardedScope($q);
                } elseif ($employeeType === 'MissingProfilePicture') {
                    $q->where('active', true)->where(function ($q2) {
                        $q2->where(function ($q3) {
                            $q3->whereNull('profile_picture')->orWhere('profile_picture', '');
                        })->whereDoesntHave('employeeDocuments', function ($d) {
                            $d->where('document_type', EmployeeDocument::DOCUMENT_TYPE_PROFILE_PICTURE);
                        });
                    })->where('created_at', '>', '2026-04-01');
                    $onboarded1099Scope($q);
                } else {
                    $q->where('employee_type', $employeeType);
                }
            })
            ->when($companyIds !== null, function ($q) use ($companyIds) {
                $q->where(function ($query) use ($companyIds) {
                    $query
                        ->whereHas('employeeRates', function ($q) use ($companyIds) {
                            $q->whereIn('company_id', $companyIds);
                        })
                        ->orWhereHas('employeeRatesRequests', function ($q) use ($companyIds) {
                            $q->whereIn('company_id', $companyIds);
                        })
                        ->orWhere(function ($q) {
                            $q->whereDoesntHave('employeeRates')
                                ->whereDoesntHave('employeeRatesRequests');
                        });
                });
            })
            ->where(function ($q) {
                $q->whereHas('employeeRates', function ($q) {
                    $q->authorizedCompanies('company_id');
                })
                    ->orWhereHas('employeeRatesRequests', function ($q) {
                        $q->authorizedCompanies('company_id');
                    })->orWhere(function ($q) {
                        $q->whereDoesntHave('employeeRates')
                            ->whereDoesntHave('employeeRatesRequests');
                    });
            })
            ->authorizedWorkgroup();
    }

    private function formatEmployeeCompanyNames(Employee $employee): string
    {
        $names = collect()
            ->merge($employee->employeeRates ?? [])
            ->merge($employee->employeeRatesRequests ?? [])
            ->map(fn($rate) => $rate->company?->name)
            ->filter()
            ->unique()
            ->values();

        return $names->isEmpty() ? 'N/A' : $names->implode(', ');
    }
    public function create()
    {
        $item = [
            'name' => '',
            'code' => '',
            'active' => true,
            'aliases' => [],
        ];
        return to_json([
            'form' => $item,
        ]);
    }

    /**
     * Upload profile picture (returns path to store on employee).
     * When employee_id is provided, updates the employee's profile_picture and document.
     * Used from employee rate request form when rate type is 1099.
     */
    public function uploadProfilePicture(Request $request, FileUploadService $fileUploadService)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,jpg,png|' . upload_max_file_size_rule(),
            'employee_id' => 'nullable|exists:employee,id',
        ]);

        try {
            $result = $fileUploadService->store(
                $request->file('file'),
                'employee-profile-pictures',
                'public'
            );
            $path = $result['path'];
            $url = $result['url'] ?? null;

            if ($request->filled('employee_id')) {
                $employee = Employee::findOrFail($request->employee_id);
                $employee->update(['profile_picture' => $path]);
                uploadDocument('profile_picture', $path, $employee->id);
            }

            return to_json([
                'path' => $path,
                'url' => $url,
            ]);
        } catch (\Exception $e) {
            return to_json([
                'message' => 'Failed to upload profile picture.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    protected static function hasAny1099Rate(array $rates): bool
    {
        $types = ['Payroll 1099', '1099 Regular', '1099 Slab', '1099 1099'];
        foreach ($rates as $rate) {
            $rt = $rate['rate_type'] ?? null;
            if ($rt && in_array($rt, $types, true)) {
                return true;
            }
        }
        return false;
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $rules = [
                'employee_id' => 'required',
                'pos_name' => 'required',
                // 'check_name' => 'required',
                'hire_date' => 'required',
                'active' => 'required|boolean',
                'aliases' => 'nullable|array',
                'aliases.*.alias_employee_id' => 'nullable|string|max:255',
                'aliases.*.alias_name' => 'nullable|string|max:255',
            ];
            $rates = $request->employee_rates ?? [];
            // if (static::hasAny1099Rate($rates)) {
            //     $rules['profile_picture'] = 'required|string';
            // }
            $request->validate($rules);
            $workgroup = session('workgroup');

            $submittedNames = collect([$request->pos_name])
                ->merge(collect($request->aliases ?? [])->pluck('alias_name'))
                ->map(fn($n) => trim((string) $n))
                ->filter()
                ->unique()
                ->values()
                ->all();
            $submittedIds = collect([$request->employee_id])
                ->merge(collect($request->aliases ?? [])->pluck('alias_employee_id'))
                ->map(fn($id) => trim((string) $id))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $existing = Employee::where('workgroup_id', $workgroup)
                ->where(function ($q) use ($submittedNames) {
                    $q->whereIn('pos_name', $submittedNames)
                        ->orWhereHas('aliases', fn($a) => $a->whereIn('alias_name', $submittedNames));
                })
                ->where(function ($q) use ($submittedIds) {
                    $q->whereIn('employee_id', $submittedIds)
                        ->orWhereHas('aliases', fn($a) => $a->whereIn('alias_employee_id', $submittedIds));
                })
                ->exists();

            if ($existing) {
                return to_json([
                    'saved' => false,
                    'message' => 'Employee already exists',
                ], 400);
            }
            $employee = Employee::create($request->only((new Employee)->getFillable()));
            $employee->syncAliases($request->aliases);

            if ($request->hasFile('profile_picture')) {
                uploadDocument('profile_picture', $request->profile_picture, $employee->id);
            }

            $employeeRates = [];
            $rates = array_merge($request->employee_rates, $request->employee_rate_requests);
            foreach ($rates as $rate) {
                if ($rate['check_payment_amount'] && $rate['check_payment_amount'] > 100 && $rate['check_payment_type'] === 'percentage') {
                    $check_payment_amount = 100;
                } else if ($rate['check_payment_amount'] && $rate['check_payment_amount'] < 0) {
                    $check_payment_amount = 0;
                } else if ($rate['check_payment_amount'] && $rate['check_payment_type'] === 'fixed') {
                    $check_payment_amount = $rate['check_payment_amount'];
                } else if ($rate['check_payment_amount'] && $rate['check_payment_type'] === 'percentage') {
                    $check_payment_amount = $rate['check_payment_amount'];
                } else {
                    $check_payment_amount = 0;
                }
                $employeeRates[] = [
                    'employee_rate_id' => null,
                    'employee_id' => $employee->id,
                    'role_id' => $rate['role_id'],
                    'pay_type' => $rate['pay_type'],
                    'rate_type' => $rate['rate_type'],
                    'payroll_type' => $rate['payroll_type'] ?? null,
                    'rate' => $rate['rate'] ?? 0,
                    'ten99_rate' => $rate['ten99_rate'] ?? 0,
                    'slab_first_hours' => $rate['slab_first_hours'] ?? 0,
                    'slab_rest_rate' => $rate['slab_rest_rate'] ?? 0,
                    'effective_date' => $rate['effective_date'],
                    'till_date' => $rate['till_date'] ?? null,
                    'payroll_hours_type' => $rate['payroll_hours_type'] ?? 'fixed',
                    'payroll_hours' => $rate['payroll_hours'] ?? 0,
                    'check_payment_type' => $rate['check_payment_type'] ?? 'fixed',
                    'check_payment_amount' => $check_payment_amount,
                    'company_id' => $rate['company_id'] ?? 0,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                    'status' => 'pending',
                    'notes' => null,
                    'hr_approved' => false,
                    'hr_approved_at' => null,
                    'hr_approved_by' => null,
                    'do_approved' => false,
                    'do_approved_at' => null,
                    'do_approved_by' => null,
                    'admin_approved' => false,
                    'admin_approved_at' => null,
                    'admin_approved_by' => null,
                    'rejected_by' => null,
                    'rejected_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ];
            }
            EmployeeRateRequest::insert($employeeRates);

            $workgroupId = (int) ($employee->workgroup_id ?? 0);
            if ($workgroupId > 0) {
                $companyIds = array_values(array_unique(array_filter(
                    array_map('intval', array_column($employeeRates, 'company_id')),
                    static fn(int $id) => $id > 0
                )));
                $companies = $companyIds === []
                    ? collect()
                    : Company::query()->whereIn('id', $companyIds)->get(['id', 'name', 'store_number'])->keyBy('id');

                $rateLines = [];
                foreach ($employeeRates as $row) {
                    $cid = (int) ($row['company_id'] ?? 0);
                    $c = $companies->get($cid);
                    $companyLabel = '—';
                    if ($c) {
                        $companyLabel = trim(($c->store_number ? $c->store_number . ' - ' : '') . ($c->name ?? ''));
                    }
                    $eff = $row['effective_date'] ?? null;
                    $rateLines[] = [
                        'company' => $companyLabel !== '' ? $companyLabel : '—',
                        'rate' => $row['rate'] ?? 0,
                        'effective_date' => $eff ? date('m/d/Y', strtotime((string) $eff)) : '—',
                        'rate_type' => (string) ($row['rate_type'] ?? ''),
                    ];
                }

                $employeeName = trim(implode(' ', array_filter([
                    $employee->first_name ?? '',
                    $employee->last_name ?? '',
                ])));
                if ($employeeName === '') {
                    $employeeName = (string) ($employee->pos_name ?? '');
                }

                NewHireNotificationService::notify([
                    'workgroup_id' => $workgroupId,
                    'rate_company_ids' => array_column($employeeRates, 'company_id'),
                    'employee' => [
                        'name' => $employeeName,
                        'email' => (string) ($employee->email ?? ''),
                        'employee_id' => (string) ($employee->employee_id ?? ''),
                        'ssn' => NewHireNotificationService::maskSsn($employee->ssn ?? null),
                        'position' => (string) ($employee->pos_name ?? ''),
                    ],
                    'rates' => $rateLines,
                ]);
            }

            $payrollRate = EmployeeRateRequest::where('employee_id', $employee->id)->whereIn('rate_type', ['Payroll 1099', 'Payroll Regular', 'Payroll Slab'])->first();
            if (!$payrollRate) {
                $employee->update(['onboarding_status' => 'verified', 'employee_type' => 'New']);
            }
            // EmployeeRates::insert($employeeRates);
            if (isset($request->profile_picture) && $request->profile_picture) {
                uploadDocument('profile_picture', $request->profile_picture, $employee->id, null, EmployeeDocument::DOCUMENT_TYPE_PROFILE_PICTURE);
            }

            DB::commit();
            // WeeklySummaryRecalculationService::recalculateForEmployee($employee->id, null, null, $employee->company_id);
            return to_json([
                'id' => $employee->id,
                'saved' => true,
                'message' => 'Employee created successfully',
            ]);
        } catch (\Exception $e) {
            dd($e);
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'Employee creation failed',
            ], 500);
        }
    }
    public function show(Request $request, $id)
    {

        $employeeType = $request->query('employee_type') ?? 'Completed';

        $employee = Employee::with(
            'employeeRates',
            'employeeRatesRequests',
            'employeeInactive',
            'workgroup',
            'aliases'
        )->authorizedWorkgroup()->where(function ($q) {
            $q->where(function ($query) {
                $query
                    ->whereHas('employeeRates')
                    ->orWhereHas('employeeRatesRequests')
                    ->orWhere(function ($q) {
                        $q->whereDoesntHave('employeeRates')
                            ->whereDoesntHave('employeeRatesRequests');
                    });
            });
        })
            // ->where(function ($q) use ($employeeType) {
            //     if ($employeeType === 'Completed') {
            //         $q->where('employee_type', 'Completed')->orWhereHas('employeeRates');
            //     } elseif ($employeeType === 'New') {
            //         $q->where('employee_type', 'New')->where(function ($q2) {
            //             $q2->orWhereDoesntHave('onboardingList')->orWhere('mail_sent', false)->orWhere('rejected', true);
            //         });
            //     } else {
            //         $q->where('employee_type', $employeeType);
            //     }
            // })

            ->findOrFail($id);


        if (!$employee) {
            return to_json([
                'model' => null,
                'message' => 'Employee not found',
            ], 404);
        }
        return to_json([
            'model' => $employee,
        ]);
    }
    public function edit(Request $request, $id)
    {
        $employeeType = $request->query('employee_type') ?? 'Completed';
        $employee = Employee::with('employeeRates', 'employeeRatesRequests', 'workgroup', 'aliases')->authorizedWorkgroup()->where(function ($q) {
            $q->where(function ($query) {
                $query
                    ->whereHas('employeeRates')
                    ->orWhereHas('employeeRatesRequests')
                    ->orWhere(function ($q) {
                        $q->whereDoesntHave('employeeRates')
                            ->whereDoesntHave('employeeRatesRequests');
                    });
            });
        })
            ->where(function ($q) use ($employeeType) {
                if ($employeeType === 'Completed') {
                    $q->where('employee_type', 'Completed')->orWhereHas('employeeRates')->orWhere(function ($q) {
                        $q->whereDoesntHave('employeeRates')
                            ->whereDoesntHave('employeeRatesRequests');
                    });;
                } elseif ($employeeType === 'New') {
                    $q->where('employee_type', 'New')->where(function ($q2) {
                        $q2->orWhereDoesntHave('onboardingList')->orWhere('mail_sent', false)->orWhere('rejected', true);
                    });
                } else if ($employeeType === 'Existing') {
                    $q->where('employee_type', $employeeType);
                }
            })
            ->findOrFail($id);

        if (!$employee) {
            return to_json([
                'model' => null,
                'message' => 'Employee not found',
            ], 404);
        }
        $lastReview = EmployeeWeeklySummary::where('employee_id', $employee->id)->where('is_reviewed', true)->orderBy('eow', 'desc')->first();
        $employee->lastReviewDate = $lastReview ? $lastReview->eow : null;


        return to_json([
            'form' => $employee,
        ]);
    }
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $employee = Employee::with('employeeRates')->where(function ($q) {
                $q->where(function ($query) {
                    $query
                        ->whereHas('employeeRates')
                        ->orWhereHas('employeeRatesRequests')
                        ->orWhere(function ($q) {
                            $q->whereDoesntHave('employeeRates')
                                ->whereDoesntHave('employeeRatesRequests');
                        });
                });
            })->findOrFail($id);
            if (!$employee) {
                return to_json([
                    'model' => null,
                    'message' => 'Employee not found',
                ], 404);
            }
            $allRates = array_merge($request->employee_rates ?? [], $request->employee_rate_requests ?? []);
            // if (static::hasAny1099Rate($allRates)) {
            //     $request->validate(['profile_picture' => 'required|string']);
            // }
            $request->validate([
                'aliases' => 'nullable|array',
                'aliases.*.alias_employee_id' => 'nullable|string|max:255',
                'aliases.*.alias_name' => 'nullable|string|max:255',
            ]);


            /* --------------------------------------------------
             start of rate request code
             -------------------------------------------------- */

            if (isset($request->profile_picture) && $request->profile_picture) {
                uploadDocument('profile_picture', $request->profile_picture, $employee->id, null, EmployeeDocument::DOCUMENT_TYPE_PROFILE_PICTURE);
            }

            $employee->update($request->only((new Employee)->getFillable()) + [
                'rejected' => false,
                'rejected_at' => null,
                'rejected_by' => null,
                'rejection_reason' => null,
            ]);
            $employee->syncAliases($request->aliases);

            $currentRates = EmployeeRates::where('employee_id', $employee->id)->get();
            $requestRates = collect($request->employee_rates ?? []);

            $result = $this->categorizeEmployeeRates($currentRates, $requestRates, $employee->id);

            $rolesCache = EmployeeRoles::select('id', 'name', 'code')->authorizedWorkgroup()->get()->keyBy('id');
            $companiesCache = Company::select('id', 'name')->get()->keyBy('id');

            $emailChangedOrNewRequests = [];
            if (!empty($result['changed_or_new'])) {
                $employeeRatesRequest = [];
                foreach ($result['changed_or_new'] as $rateData) {
                    $employeeRateId = $rateData['id'] ?? null; // Link to existing if it's an update
                    unset($rateData['id']);

                    $employeeRatesRequest[] = $this->buildRateRequestRow($rateData, $employee->id, $employeeRateId);
                    $emailChangedOrNewRequests[] = $this->formatRateRequestForEmail($rateData, $rolesCache, $companiesCache);
                }
                EmployeeRateRequest::insert($employeeRatesRequest);

                $requestedBy = Auth::user()?->name ?? 'System';
                EmployeeRateRequestNotificationService::notifyNewRequests(
                    $employee,
                    $result['changed_or_new'],
                    $emailChangedOrNewRequests,
                    $requestedBy
                );
            }

            // Update or create standalone rate requests (from Request section) 
            $newRateRequests = $request->employee_rate_requests ?? [];
            $keptRequestIds = [];
            $emailNewRequests = [];
            $emailUpdatedRequests = [];


            if (!empty($newRateRequests)) {
                foreach ($newRateRequests as $rateData) {
                    $id = $rateData['id'] ?? null;
                    unset($rateData['id']);

                    if ($id) {
                        $existing = EmployeeRateRequest::with('role', 'company')
                            ->where('id', $id)
                            ->first();

                        if ($existing) {
                            $oldData = $this->getRateRequestForComparison($existing);
                            $existing->update($this->buildRateRequestUpdateData($rateData));
                            $keptRequestIds[] = (int) $id;

                            $newData = $this->buildRateRequestForComparison($rateData, $rolesCache, $companiesCache);
                            $comparison = $this->buildComparisonChanges($oldData, $newData);
                            if ($this->hasComparisonChanges($comparison)) {
                                $emailUpdatedRequests[] = $comparison;
                            }
                            continue;
                        }
                    }
                    $employeeRatesRequest = $this->buildRateRequestRow($rateData, $employee->id, 0);
                    $created = EmployeeRateRequest::create($employeeRatesRequest);
                    $keptRequestIds[] = $created->id;

                    $emailNewRequests[] = $this->formatRateRequestForEmail($rateData, $rolesCache, $companiesCache);
                }
            }

            $requestedByStandalone = Auth::user()?->name ?? 'System';
            if (! empty($emailNewRequests)) {
                EmployeeRateRequestNotificationService::notifyNewRequests(
                    $employee,
                    $newRateRequests,
                    $emailNewRequests,
                    $requestedByStandalone
                );
            }
            // if (! empty($emailUpdatedRequests)) {
            //     EmployeeRateRequestNotificationService::notifyUpdatedRequests(
            //         $employee,
            //         $newRateRequests,
            //         $emailUpdatedRequests,
            //         $requestedByStandalone
            //     );
            // }


            /* --------------------------------------------------
            end of rate request code
             -------------------------------------------------- */

            // // $standaloneQuery = EmployeeRateRequest::where('employee_id', $employee->id)
            // //     ->where('status', 'pending')
            // //     ->where('employee_rate_id', 0);
            // // if (!empty($keptRequestIds)) {
            // //     $standaloneQuery->whereNotIn('id', $keptRequestIds);
            // // }
            // // $standaloneQuery->delete();

            /* --------------------------------------------------
            start of rate request code
             -------------------------------------------------- */


            // $employeeRate = [];
            // EmployeeRates::where('employee_id', $employee->id)->delete();
            // EmployeeRateRequest::where('employee_id', $employee->id)->delete();
            // // $allrates = [...$request->employee_rates, ...$request->employee_rate_requests];
            // $allrates = $request->employee_rates;
            // foreach ($allrates as $rate) {
            //     $payroll_type = $rate['payroll_type'] ?? null;
            //     if (!$payroll_type && ($rate['rate_type'] == 'Payroll Slab' || $rate['rate_type'] == 'Payroll Regular' || $rate['rate_type'] == 'Payroll 1099')) {
            //         $payroll_type = 'Print on site';
            //     } else if ($rate['rate_type'] == '1099 Regular' || $rate['rate_type'] == '1099 Slab') {
            //         $payroll_type = null;
            //     }
            //     $employeeRate[] = [
            //         'employee_id' => $employee->id,
            //         'role_id' => $rate['role_id'],
            //         'pay_type' => $rate['pay_type'],
            //         'rate_type' => $rate['rate_type'],
            //         'payroll_type' => $payroll_type,
            //         'rate' => $rate['rate'] ?? 0,
            //         'payroll_rate' => $rate['payroll_rate'] ?? 0,
            //         'ten99_rate' => $rate['ten99_rate'] ?? 0,
            //         'slab_first_hours' => $rate['slab_first_hours'] ?? 0,
            //         'slab_rest_rate' => $rate['slab_rest_rate'] ?? 0,
            //         'effective_date' => $rate['effective_date'],
            //         'till_date' => $rate['till_date'] ?? null,
            //         'payroll_hours' => $rate['payroll_hours'] ?? 0,
            //         'payroll_hours_type' => $rate['payroll_hours_type'] ?? 'fixed',
            //         'check_payment_type' => $rate['check_payment_type'] ?? 'fixed',
            //         'check_payment_amount' => $rate['check_payment_amount'] ?? 0,
            //         'company_id' => $rate['company_id'],
            //         'created_by' => Auth::id(),
            //         'updated_by' => Auth::id(),
            //         'till_date' => $rate['till_date'] ?? null,
            //     ];
            // }
            // if (count($employeeRate) > 0) {
            //     EmployeeRates::insert($employeeRate);
            // }
            // $employeeRateRequest = [];
            // $allrates = $request->employee_rate_requests;
            // foreach ($allrates as $rate) {
            //     $payroll_type = $rate['payroll_type'] ?? null;
            //     if (!$payroll_type && ($rate['rate_type'] == 'Payroll Slab' || $rate['rate_type'] == 'Payroll Regular' || $rate['rate_type'] == 'Payroll 1099')) {
            //         $payroll_type = 'Print on site';
            //     } else if ($rate['rate_type'] == '1099 Regular' || $rate['rate_type'] == '1099 Slab') {
            //         $payroll_type = null;
            //     }
            //     $employeeRateRequest[] = [
            //         'employee_rate_id' => null,
            //         'employee_id' => $employee->id,
            //         'role_id' => $rate['role_id'],
            //         'pay_type' => $rate['pay_type'],
            //         'rate_type' => $rate['rate_type'],
            //         'payroll_type' => $payroll_type,
            //         'rate' => $rate['rate'] ?? 0,
            //         'payroll_rate' => $rate['payroll_rate'] ?? 0,
            //         'ten99_rate' => $rate['ten99_rate'] ?? 0,
            //         'slab_first_hours' => $rate['slab_first_hours'] ?? 0,
            //         'slab_rest_rate' => $rate['slab_rest_rate'] ?? 0,
            //         'effective_date' => $rate['effective_date'],
            //         'till_date' => $rate['till_date'] ?? null,
            //         'payroll_hours' => $rate['payroll_hours'] ?? 0,
            //         'payroll_hours_type' => $rate['payroll_hours_type'] ?? 'fixed',
            //         'check_payment_type' => $rate['check_payment_type'] ?? 'fixed',
            //         'check_payment_amount' => $rate['check_payment_amount'] ?? 0,
            //         'company_id' => $rate['company_id'],
            //         'created_by' => Auth::id(),
            //         'updated_by' => Auth::id(),
            //         'till_date' => $rate['till_date'] ?? null,
            //         'status' => 'pending',
            //         'notes' => null,
            //         'hr_approved' => false,
            //         'hr_approved_at' => null,
            //         'hr_approved_by' => null,
            //         'do_approved' => false,
            //         'do_approved_at' => null,
            //         'do_approved_by' => null,
            //         'admin_approved' => false,
            //         'admin_approved_at' => null,
            //         'admin_approved_by' => null,
            //         'rejected_by' => null,
            //         'rejected_at' => null,
            //         'created_by' => Auth::id(),
            //         'updated_by' => Auth::id(),
            //         'created_at' => now(),
            //         'updated_at' => now(),
            //     ];
            // }
            // if (count($employeeRateRequest) > 0) {
            //     EmployeeRateRequest::insert($employeeRateRequest);
            // }




            /* --------------------------------------------------
            end of rate request code
             -------------------------------------------------- */


            // $emp_pos = Employee::where('pos_name', $request->pos_name)->where('id', '!=', $employee->id)->first();

            // $emp2 = Employee::where(function ($q) use ($request) {
            //     $q->where('pos_name', $request->employee_name_2)->orWhereIn('employee_id', [$request->employee_id_1, $request->employee_id_2, $request->employee_id_3]);
            // })->where('id', '!=', $employee->id)->first();
            // $emp3 = Employee::where(function ($q) use ($request) {
            //     $q->where('pos_name', $request->employee_name_3)->orWhereIn('employee_id', [$request->employee_id_1, $request->employee_id_2, $request->employee_id_3]);
            // })->where('id', '!=', $employee->id)->first();
            // $emp4 = Employee::where(function ($q) use ($request) {
            //     $q->where('pos_name', $request->pos_name)->orWhereIn('employee_id', [$request->employee_id_1, $request->employee_id_2, $request->employee_id_3]);
            // })->where('id', '!=', $employee->id)->first();


            $workgroup = session('workgroup');
            $submittedNames = collect([$request->pos_name])
                ->merge(collect($request->aliases ?? [])->pluck('alias_name'))
                ->map(fn($n) => trim((string) $n))
                ->filter()
                ->unique()
                ->values()
                ->all();
            $submittedIds = collect([$request->employee_id])
                ->merge(collect($request->aliases ?? [])->pluck('alias_employee_id'))
                ->map(fn($id) => trim((string) $id))
                ->filter()
                ->unique()
                ->values()
                ->all();

            $emps = Employee::where('id', '!=', $employee->id)
                ->where('workgroup_id', $workgroup)
                ->where(function ($q) use ($submittedNames) {
                    $q->whereIn('pos_name', $submittedNames)
                        ->orWhereHas('aliases', fn($a) => $a->whereIn('alias_name', $submittedNames));
                })
                ->where(function ($q) use ($submittedIds) {
                    $q->whereIn('employee_id', $submittedIds)
                        ->orWhereHas('aliases', fn($a) => $a->whereIn('alias_employee_id', $submittedIds));
                })
                ->with('onboardingList')
                ->get()
                ->toArray();

            if ($request->mark_as_new) {
                $employee->update(['employee_type' => $employee->employeeRates()->count() > 0 ? 'Completed' : 'New']);
            }

            $emp_names_ids = array_map(function ($emp) use ($employee) {
                return isset($emp['id']) && $emp['id'] != $employee->id ? $emp['id'] : null;
            }, $emps);
            $emp_names_ids = array_filter($emp_names_ids);



            $employee_rates = EmployeeRates::where('employee_id', $employee->id)->get();
            if (count($emp_names_ids) > 0) {
                // $mainEmployeHours = EmployeeHours::where('employee_id', $employee->id)->select('date','role_id','pay_type','company_id')->get()->keyBy( function($item) {
                //     return $item->date . '-' . $item->role_id . '-' . $item->pay_type . '-' . $item->company_id;
                // });
                // $dates=[];
                // foreach($emp_names_ids as $e){
                //     $employeeHours = EmployeeHours::where('employee_id', $e)->select('date','role_id','pay_type','company_id')->get()->keyBy( function($item) {
                //         return $item->date . '-' . $item->role_id . '-' . $item->pay_type . '-' . $item->company_id;
                //     });
                //     foreach($employeeHours as $hour){
                //         if(isset($mainEmployeHours[$hour->date . '-' . $hour->role_id . '-' . $hour->pay_type . '-' . $hour->company_id])){
                //             $dates[] = $hour->date;
                //         }
                //     }
                // }
                // if(count($dates) > 0){
                //     return to_json([
                //         'saved' => false,
                //         'message' => 'Employee hours already exists ' . implode(',', $dates) . ' in other employee',
                //     ], 500);
                // }
                EmployeeHours::whereIn('employee_id', $emp_names_ids)->update(['employee_id' => $employee->id]);
                CheckMaster::whereIn('employee_id', $emp_names_ids)->update(['employee_id' => $employee->id]);
                PayrollCheckAmount::whereIn('employee_id', $emp_names_ids)->update(['employee_id' => $employee->id]);
                Drivers::whereIn('employee_id', $emp_names_ids)->update(['employee_id' => $employee->id]);
                EmployeeDocument::whereIn('employee_id', $emp_names_ids)->update(['employee_id' => $employee->id]);
                foreach ($emps as $emp) {
                    if (!$emp) continue;

                    $emp_rates = EmployeeRates::where('employee_id', $emp['id'])->get();

                    foreach ($emp_rates as $emp_rate) {
                        // Check if this role and pay_type combination exists in parent employee
                        $exists = $employee_rates->where('role_id', $emp_rate->role_id)
                            ->where('company_id', $emp_rate->company_id)
                            ->where('pay_type', $emp_rate->pay_type)
                            ->first();

                        // If role doesn't exist in parent employee, add it
                        if (!$exists) {
                            $payroll_type = $emp_rate->payroll_type ?? null;
                            if (!$payroll_type && ($emp_rate->rate_type == 'Payroll Slab' || $emp_rate->rate_type == 'Payroll Regular' || $emp_rate->rate_type == 'Payroll 1099')) {
                                $payroll_type = 'Print on site';
                            } else if ($emp_rate->rate_type == '1099 Regular' || $emp_rate->rate_type == '1099 Slab') {
                                $payroll_type = null;
                            }
                            info($payroll_type);
                            EmployeeRates::create([
                                'employee_id' => $employee->id,
                                'role_id' => $emp_rate->role_id,
                                'pay_type' => $emp_rate->pay_type,
                                'rate_type' => $emp_rate->rate_type,
                                'payroll_type' => $payroll_type,
                                'rate' => $emp_rate->rate,
                                'payroll_rate' => $emp_rate->payroll_rate,
                                'ten99_rate' => $emp_rate['ten99_rate'],
                                'slab_first_hours' => $emp_rate->slab_first_hours,
                                'slab_rest_rate' => $emp_rate->slab_rest_rate,
                                'effective_date' => $emp_rate->effective_date,
                                'payroll_hours' => $emp_rate->payroll_hours,
                                'payroll_hours_type' => $emp_rate->payroll_hours_type,
                                'check_payment_type' => $emp_rate->check_payment_type,
                                'check_payment_amount' => $emp_rate->check_payment_amount,
                                'company_id' => $emp_rate->company_id,
                                'till_date' => $emp_rate->till_date,
                            ]);
                        }
                    }

                    $emp_rate_requests = EmployeeRateRequest::where('employee_id', $emp['id'])->get();
                    foreach ($emp_rate_requests as $emp_rate_request) {
                        $rateRequestExists = EmployeeRateRequest::where('employee_id', $employee->id)
                            ->where('role_id', $emp_rate_request->role_id)
                            ->where('company_id', $emp_rate_request->company_id)
                            ->where('pay_type', $emp_rate_request->pay_type)
                            ->first();
                        $employeeRate = EmployeeRates::where('employee_id', $employee->id)
                            ->where('role_id', $emp_rate_request->role_id)
                            ->where('company_id', $emp_rate_request->company_id)
                            ->where('pay_type', $emp_rate_request->pay_type)
                            ->first();
                        if (!$rateRequestExists && !$employeeRate) {
                            EmployeeRateRequest::create([
                                'employee_rate_id' => null,
                                'employee_id' => $employee->id,
                                'company_id' => $emp_rate_request->company_id,
                                'role_id' => $emp_rate_request->role_id,
                                'pay_type' => $emp_rate_request->pay_type,
                                'rate_type' => $emp_rate_request->rate_type ?? 'Payroll Regular',
                                'payroll_type' => $emp_rate_request->payroll_type ?? null,
                                'rate' => $emp_rate_request->rate,
                                'payroll_rate' => $emp_rate_request->payroll_rate,
                                'ten99_rate' => $emp_rate_request->ten99_rate,
                                'slab_first_hours' => $emp_rate_request->slab_first_hours ?? 0,
                                'slab_rest_rate' => $emp_rate_request->slab_rest_rate ?? 0,
                                'payroll_hours' => $emp_rate_request->payroll_hours ?? 0,
                                'payroll_hours_type' => $emp_rate_request->payroll_hours_type ?? 'fixed',
                                'check_payment_type' => $emp_rate_request->check_payment_type ?? 'fixed',
                                'check_payment_amount' => $emp_rate_request->check_payment_amount ?? 0,
                                'effective_date' => $emp_rate_request->effective_date,
                                'till_date' => $emp_rate_request->till_date ?? null,
                                'status' => 'pending',
                                'created_by' => Auth::id(),
                                'updated_by' => Auth::id(),
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
                EmployeeRateRequest::whereIn('employee_id', $emp_names_ids)->delete();
                EmployeeHoursRequest::whereIn('employee_id', $emp_names_ids)->delete();
                EmployeeInactive::whereIn('employee_id', $emp_names_ids)->delete();
                EmployeeRates::whereIn('employee_id', $emp_names_ids)->delete();
                Employee::whereIn('id', $emp_names_ids)->delete();
                WeeklySummaryRecalculationService::recalculateForEmployee(['employee_id' => $employee->id]);
            }

            $payrollRate = EmployeeRateRequest::where('employee_id', $employee->id)->whereIn('rate_type', ['Payroll 1099', 'Payroll Regular', 'Payroll Slab'])->first();
            if (!$payrollRate || $this->skipOnboarding) {
                $employee->update(['onboarding_status' => 'verified']);
            }

            if (!$employee->onboardingList && $employee->employee_type != 'Completed') {
                $onboardingList = array_filter($emps, function ($emp) use ($employee) {
                    return $emp['id'] != $employee->id;
                });
                foreach ($onboardingList as $emp) {
                    $onboardingList = OnboardingList::where('employee_id', $emp['id'])->first();
                    if ($onboardingList) {
                        $onboardingList->update(['employee_id' => $employee->id]);
                        $employee->update(['employee_type' => $emp['employee_type']]);
                        break;
                    }
                }
            }


            DB::commit();


            return to_json([
                'message' => 'Employee updated successfully',
                'saved' => true,
                'id' => $employee->id,
            ]);
        } catch (\Exception $e) {
            dd($e);
            DB::rollBack();
            return to_json([
                'saved' => false,
                'message' => 'Employee update failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function inactive($id)
    {
        $this->authorize('access', 'employee.update');

        $employee = Employee::with('employeeRates')->where(function ($q) {
            $q->where(function ($query) {
                $query
                    ->whereHas('employeeRates')
                    ->orWhereHas('employeeRatesRequests')
                    ->orWhere(function ($q) {
                        $q->whereDoesntHave('employeeRates')
                            ->whereDoesntHave('employeeRatesRequests');
                    });
            });
        })->findOrFail($id);

        if (!$employee->active) {
            return to_json([
                'inactive' => false,
                'message' => 'Employee is already inactive',
            ], 422);
        }

        try {
            $employee->update([
                'active' => false,
                'updated_by' => Auth::id(),
            ]);

            return to_json([
                'inactive' => true,
                'message' => 'Employee inactivated successfully',
            ]);
        } catch (\Exception $e) {
            return to_json([
                'inactive' => false,
                'message' => 'Employee inactivation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $employee = Employee::with('employeeRates')->where(function ($q) {
            $q->where(function ($query) {
                $query
                    ->whereHas('employeeRates')
                    ->orWhereHas('employeeRatesRequests')
                    ->orWhere(function ($q) {
                        $q->whereDoesntHave('employeeRates')
                            ->whereDoesntHave('employeeRatesRequests');
                    });
            });
        })->findOrFail($id);
        if (!$employee) {
            return to_json([
                'model' => null,
                'message' => 'Employee not found',
            ], 404);
        }
        DB::beginTransaction();
        try {
            $checkMaster = CheckMaster::where('employee_id', $employee->id)->first();
            if ($checkMaster) {
                return to_json([
                    'deleted' => false,
                    'message' => 'Employee has checks, cannot delete',
                ], 500);
            }
            $employeeHours = EmployeeHours::where('employee_id', $employee->id)->count();
            if ($employeeHours > 0) {
                return to_json([
                    'deleted' => false,
                    'message' => 'Employee has hours records, cannot delete',
                ], 500);
            }


            EmployeeRates::where('employee_id', $employee->id)->delete();
            EmployeeDocument::where('employee_id', $employee->id)->delete();
            EmployeeRateRequest::where('employee_id', $employee->id)->delete();
            OnboardingList::where('employee_id', $employee->id)->delete();
            $employee->delete();
            WeeklySummaryRecalculationService::recalculateForEmployee(['employee_id' => $id]);
            DB::commit();
            return to_json([
                'deleted' => true,
                'message' => 'Employee deleted successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'deleted' => false,
                'message' => 'Employee deletion failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function search(Request $request)
    {
        $search = $request->query('query');
        $column = 'employee.pos_name';
        $company_id = $request->query('company_id');
        $employees = Employee::with('employeeRates')->where(function ($q) {
            $q->where(function ($query) {
                $query
                    ->whereHas('employeeRates')
                    ->orWhereHas('employeeRatesRequests')
                    ->orWhere(function ($q) {
                        $q->whereDoesntHave('employeeRates')
                            ->whereDoesntHave('employeeRatesRequests');
                    });
            });
        })->when($search, function ($query) use ($column, $search) {
            return $query->where($column, 'like', '%' . $search . '%')->orWhere('employee.employee_id', 'like', '%' . $search . '%');
        })->when($company_id, function ($query) use ($company_id) {
            return $query->join('employee_rates', 'employee_rates.employee_id', '=', 'employee.id')
                ->where('employee_rates.company_id', $company_id);
        })->where('active', 1)->selectRaw('employee.id, CONCAT(employee.employee_id, " - ", employee.pos_name) as pos_name, employee.ssn')
            ->where('workgroup_id', session('workgroup'))
            ->get();
        return to_json([
            'collection' => $employees,
        ]);
    }

    /**
     * Categorize employee rates into unchanged (apply directly) vs changed/new (approval).
     * Returns ['unchanged' => [...], 'changed_or_new' => [...]]
     */
    protected function categorizeEmployeeRates($currentRates, $requestRates, $employeeId): array
    {
        $normalize = function ($row) {
            return [
                'id' => (int) ($row['id'] ?? $row->id ?? 0),
                'company_id' => (int) ($row['company_id'] ?? $row->company_id ?? 0),
                'role_id' => (int) ($row['role_id'] ?? $row->role_id ?? 0),
                'pay_type' => (string) ($row['pay_type'] ?? $row->pay_type ?? ''),
                'rate_type' => (string) ($row['rate_type'] ?? $row->rate_type ?? ''),
                'rate' => (float) ($row['rate'] ?? $row->rate ?? 0),
                'payroll_rate' => (float) ($row['payroll_rate'] ?? $row->payroll_rate ?? 0),
                'ten99_rate' => (float) ($row['ten99_rate'] ?? $row['ten99_rate'] ?? 0),
                'slab_first_hours' => (int) ($row['slab_first_hours'] ?? $row->slab_first_hours ?? 0),
                'slab_rest_rate' => (float) ($row['slab_rest_rate'] ?? $row->slab_rest_rate ?? 0),
                'effective_date' => date('Y-m-d', strtotime($row['effective_date'] ?? $row->effective_date ?? 'now')),
                'till_date' => $row['till_date'] ? date('Y-m-d', strtotime($row['till_date'])) : null,
                'payroll_hours' => (int) ($row['payroll_hours'] ?? $row->payroll_hours ?? 0),
                'check_payment_type' => (string) ($row['check_payment_type'] ?? $row->check_payment_type ?? 'fixed'),
                'check_payment_amount' => (float) ($row['check_payment_amount'] ?? $row->check_payment_amount ?? 0),
                'employee_rate_id' => (int) ($row['employee_rate_id'] ?? $row->employee_rate_id ?? 0),
                'payroll_hours_type' => (string) ($row['payroll_hours_type'] ?? $row->payroll_hours_type ?? 'fixed'),
            ];
        };

        // Build a map of current rates keyed by role_id|pay_type|effective_date
        $currentMap = [];
        foreach ($currentRates as $current) {
            $normalized = $normalize($current);

            $normalized['id'] = $current->id;
            $key = '';
            foreach ($normalized as $keyString => $value) {
                if ($keyString == 'employee_rate_id' || $keyString == 'id') continue;
                $key .= $keyString . '|' . $value . '|';
            }
            $currentMap[$key] = $normalized;
        }


        $unchanged = [];
        $changedOrNew = [];

        $employeeRateIds = array_column($requestRates->toArray(), 'id');
        $rates = EmployeeRates::whereIn('id', $employeeRateIds)->get()->keyBy('id');

        foreach ($requestRates as $requestRate) {
            $normalized = $normalize($requestRate);

            $key = '';
            foreach ($normalized as $keyString => $value) {
                if ($keyString == 'employee_rate_id' || $keyString == 'id') continue;
                $key .= $keyString . '|' . $value . '|';
            }
            if (isset($currentMap[$key]) && $normalized['employee_rate_id'] == 0) {
                $unchanged[] = $normalized;
            } else {
                // New rate: needs approval
                if ($normalized['employee_rate_id']) {
                    $normalized['id'] = $normalized['employee_rate_id'];
                }
                unset($normalized['employee_rate_id']);
                $changedOrNew[] = $normalized;
            }

            $rate = $rates[$requestRate['id']] ?? null;
            if ($rate) {
                $rate->update([
                    'payroll_type' => $requestRate['payroll_type'] ?? null,
                ]);
            }
        }

        // Rates in currentMap that weren't in request were removed by user
        // For now, we'll let them be deleted naturally (not re-inserted)

        return [
            'unchanged' => $unchanged,
            'changed_or_new' => $changedOrNew,
        ];
    }

    /**
     * Build a single EmployeeRatesRequest row for insert.
     */
    protected function buildRateRequestRow(array $rateData, int $employeeId, ?int $employeeRateId): array
    {
        $checkPaymentAmount = $rateData['check_payment_amount'] ?? 0;
        if (isset($rateData['check_payment_amount']) && $rateData['check_payment_amount'] > 100 && ($rateData['check_payment_type'] ?? 'fixed') === 'percentage') {
            $checkPaymentAmount = 100;
        } elseif (isset($rateData['check_payment_amount']) && $rateData['check_payment_amount'] < 0) {
            $checkPaymentAmount = 0;
        }

        return [
            'employee_rate_id' => $employeeRateId ?? 0,
            'employee_id' => $employeeId,
            'company_id' => $rateData['company_id'] ?? 0,
            'role_id' => $rateData['role_id'] ?? 0,
            'pay_type' => $rateData['pay_type'] ?? 'HR',
            'rate_type' => $rateData['rate_type'] ?? 'Payroll Regular',
            'payroll_type' => $rateData['payroll_type'] ?? null,
            'rate' => $rateData['rate'] ?? 0,
            'payroll_rate' => $rateData['payroll_rate'] ?? 0,
            'ten99_rate' => $rateData['ten99_rate'] ?? 0,
            'slab_first_hours' => $rateData['slab_first_hours'] ?? 0,
            'slab_rest_rate' => $rateData['slab_rest_rate'] ?? 0,
            'effective_date' => $rateData['effective_date'] ?? now()->format('Y-m-d'),
            'till_date' => $rateData['till_date'] ?? null,
            'payroll_hours_type' => $rateData['payroll_hours_type'] ?? 'fixed',
            'payroll_hours' => $rateData['payroll_hours'] ?? 0,
            'check_payment_type' => $rateData['check_payment_type'] ?? 'fixed',
            'check_payment_amount' => $checkPaymentAmount,
            'status' => 'pending',
            'notes' => null,
            'hr_approved' => false,
            'hr_approved_at' => null,
            'hr_approved_by' => null,
            'do_approved' => false,
            'do_approved_at' => null,
            'do_approved_by' => null,
            'admin_approved' => false,
            'admin_approved_at' => null,
            'admin_approved_by' => null,
            'rejected_by' => null,
            'rejected_at' => null,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Build update data for an existing EmployeeRatesRequest (pending standalone).
     */
    protected function buildRateRequestUpdateData(array $rateData): array
    {
        $checkPaymentAmount = $rateData['check_payment_amount'] ?? 0;
        if (isset($rateData['check_payment_amount']) && $rateData['check_payment_amount'] > 100 && ($rateData['check_payment_type'] ?? 'fixed') === 'percentage') {
            $checkPaymentAmount = 100;
        } elseif (isset($rateData['check_payment_amount']) && $rateData['check_payment_amount'] < 0) {
            $checkPaymentAmount = 0;
        }

        return [
            'company_id' => $rateData['company_id'] ?? 0,
            'role_id' => $rateData['role_id'] ?? 0,
            'pay_type' => $rateData['pay_type'] ?? 'HR',
            'rate_type' => $rateData['rate_type'] ?? 'Payroll Regular',
            'payroll_type' => $rateData['payroll_type'] ?? null,
            'rate' => $rateData['rate'] ?? 0,
            'payroll_rate' => $rateData['payroll_rate'] ?? 0,
            'ten99_rate' => $rateData['ten99_rate'] ?? 0,
            'slab_first_hours' => $rateData['slab_first_hours'] ?? 0,
            'slab_rest_rate' => $rateData['slab_rest_rate'] ?? 0,
            'effective_date' => $rateData['effective_date'] ?? now()->format('Y-m-d'),
            'till_date' => $rateData['till_date'] ?? null,
            'payroll_hours_type' => $rateData['payroll_hours_type'] ?? 'fixed',
            'payroll_hours' => $rateData['payroll_hours'] ?? 0,
            'check_payment_type' => $rateData['check_payment_type'] ?? 'fixed',
            'check_payment_amount' => $checkPaymentAmount,
            'updated_by' => Auth::id(),
            'updated_at' => now(),
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
            'payroll_hours' => $req->payroll_hours ? $req->payroll_hours . ' ' . ($req->payroll_hours_type == 'percentage' ? '%' : 'Hours') : '-',
            'check_payment_amount' => $req->check_payment_type === 'percentage'
                ? ($req->check_payment_amount . '%')
                : (string) $req->check_payment_amount,
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
            'payroll_type' => $data['payroll_type'] ?? '-',
            'rate' => $rate,
            'effective_date' => $data['effective_date'] ?? '-',
            'till_date' => $data['till_date'] ?? '-',
            'slab_first_hours' => $data['slab_first_hours'] ?? '-',
            'slab_rest_rate' => $data['slab_rest_rate'] ?? '-',
            'payroll_hours' => $data['payroll_hours'] ? $data['payroll_hours'] . ' ' . ($data['payroll_hours_type'] == 'percentage' ? '%' : 'Hours') : '-',
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
        $oldRateType = $old['rate_type'] ?? '';
        $newRateType = $new['rate_type'] ?? '';

        $oldRate = strtolower($oldRateType);
        $newRate = strtolower($newRateType);

        $isPayrollRegular = in_array('payroll regular', [$oldRate, $newRate]);
        $is1099Slab    = in_array('1099 slab', [$oldRate, $newRate]);
        $is1099Regular = in_array('1099 regular', [$oldRate, $newRate]);
        $isPayrollSlab = in_array('payroll slab', [$oldRate, $newRate]);

        foreach ($labels as $key => $label) {

            // ----------- Skip rules ----------
            if (
                in_array($key, ['slab_first_hours', 'slab_rest_rate']) &&
                ($isPayrollRegular || $is1099Regular) && !$isPayrollSlab && !$is1099Slab
            ) {
                continue;
            }

            if ($key === 'check_payment_amount' && $isPayrollRegular && !$isPayrollSlab && !$is1099Slab && !$is1099Regular) {
                continue;
            }

            if (
                in_array($key, ['payroll_hours']) && $isPayrollRegular &&  !$isPayrollSlab
            ) {
                continue;
            }

            // ----------- Value formatting ----------
            $oldValue = $old[$key] ?? null;
            $newValue = $new[$key] ?? null;

            // ----------- Default ----------
            $changes[$key] = [
                'label' => $label,
                'old'   => (is_numeric($oldValue) ? number_format((float) $oldValue, 2) : $oldValue) ?: '-',
                'new'   => (is_numeric($newValue) ? number_format((float) $newValue, 2) : $newValue) ?: '-',
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
     * Format rate request for new request email.
     */
    protected function formatRateRequestForEmail(array $data, $rolesCache, $companiesCache): array
    {
        $role = $rolesCache->get($data['role_id'] ?? null);
        $company = $companiesCache->get($data['company_id'] ?? null);
        $rate = $data['rate'] ?? 0;
        $ten99Rate = $data['ten99_rate'] ?? 0;
        return [
            'company' => $company?->name ?? '-',
            'role' => $role ? ($role->code . ' - ' . $role->name) : '-',
            'pay_type' => $data['pay_type'] ?? '-',
            'rate_type' => $data['rate_type'] ?? '-',
            'payroll_type' => $data['payroll_type'] ?? '-',
            'rate' => $rate,
            'ten99_rate' => $ten99Rate,
            'slab_first_hours' => $data['slab_first_hours'] ?? '-',
            'slab_rest_rate' => $data['slab_rest_rate'] ?? '-',
            'rate_formatted' => '$' . number_format((float) $rate, 2),
            'effective_date' => $data['effective_date'] ?? '-',
            'till_date' => $data['till_date'] ?? '-',
            'payroll_hours' => $data['payroll_hours'] ? $data['payroll_hours'] . ' ' . ($data['payroll_hours_type'] == 'percentage' ? '%' : 'Hours') : '-',
            'check_payment_amount' => $data['check_payment_type'] === 'percentage' ? ($data['check_payment_amount'] . '%') : (string) $data['check_payment_amount'],
        ];
    }
    public function getDocuments($id)
    {
        $documents = EmployeeDocument::where('employee_id', $id)->with('createdBy', 'updatedBy')->get();
        return to_json([
            'collection' => $documents,
        ]);
    }

    public function storeDocument(Request $request, FileUploadService $fileUploadService)
    {
        $request->validate([
            'employee_id' => 'required|exists:employee,id',
            'document_name' => 'required|string|max:255',
            'file' => 'required|file|' . upload_max_file_size_rule() . '|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx,csv,txt',
            'document_type' => 'required|in:' . implode(',', EmployeeDocument::getDocumentTypes()),
        ]);

        $employee = Employee::find($request->employee_id);
        try {
            $result = $fileUploadService->store(
                $request->file('file'),
                'employee-documents/' . $request->employee_id,
                'public'
            );

            if ($request->document_type === EmployeeDocument::DOCUMENT_TYPE_PROFILE_PICTURE) {
                $employee->update(['profile_picture' => $result['path']]);
            }

            $document = EmployeeDocument::create([
                'employee_id' => $request->employee_id,
                'document_name' => $request->document_name,
                'document_path' => $result['path'],
                'document_type' => $request->document_type,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ])->load('createdBy', 'updatedBy');

            return to_json([
                'saved' => true,
                'message' => 'Document uploaded successfully.',
                'model' => $document,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return to_json([
                'saved' => false,
                'message' => 'Failed to upload document.',
            ], 500);
        }
    }

    public function destroyDocument(EmployeeDocument $document, FileUploadService $fileUploadService)
    {
        try {
            // if (! empty($document->document_path)) {
            //     $fileUploadService->delete($document->document_path);
            // }
            $document->delete();

            return to_json([
                'deleted' => true,
                'message' => 'Document deleted successfully.',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return to_json([
                'deleted' => false,
                'message' => 'Failed to delete document.',
            ], 500);
        }
    }

    public function getPayrollInfo(Request $request)
    {
        $isDC = isDCWorkgroup();
        $employee = Employee::find($request->employee_id);

        $oldestEmployeeWeeklySummary = EmployeeWeeklySummary::where('employee_id', $employee->id)->authorizedCompanies('company_id')->orderBy('eow', 'asc')->first();
        if (!$oldestEmployeeWeeklySummary) {
            return to_json([
                'saved' => true,
                'message' => 'Payroll info fetched successfully.',
                'data' => [],
            ]);
        }

        $year = Carbon::parse($oldestEmployeeWeeklySummary->eow)->year ?? Carbon::now()->year;

        $yearStart = Carbon::parse($year . '-01-01');

        $dayOfWeek = (int)$yearStart->format('N');

        $isDC = isDCWorkgroup();
        if ($isDC) {
            $daysToMonday = $dayOfWeek === 7 ? 0 : 1 - $dayOfWeek;
        } else {
            $daysToMonday = $dayOfWeek === 7 ? -6 : 1 - $dayOfWeek - 7;
        }
        $firstMonday = $yearStart->copy()->addDays($daysToMonday);

        $eows = [];
        $i = 0;
        $startDate = $firstMonday;
        $endDate = $firstMonday->copy()->addDays(($i * 14) + 13);
        $eows[] = ['start' => $startDate->format('Y-m-d'), 'end' => $endDate->format('Y-m-d')];
        $collection = [];
        while ($endDate->year <= Carbon::now()->year) {
            $startDate = $endDate->addDays(1);
            $endDate = $startDate->copy()->addDays(13);
            $eows[] = ['start' => $startDate->format('Y-m-d'), 'end' => $endDate->format('Y-m-d')];
            $i++;
        }
        $eows = array_reverse($eows);
        foreach ($eows as $eow) {
            $startDate = Carbon::parse($eow['start']);
            $endDate = Carbon::parse($eow['end']);

            $weeklySummary = EmployeeWeeklySummary::where('employee_id', $employee->id)
                ->groupBy('role_id', 'company_id', 'eow')
                ->whereBetween('eow', [$startDate, $endDate])
                ->authorizedCompanies('company_id')
                ->selectRaw('eow, role_id, company_id, sum(total_hours) as total_hours, sum(payroll_methods) as payroll_methods, sum(tips) as tips, sum(tips_due) as tips_due, sum(mileage_due) as mileage_due, sum(gross_pay) as gross_pay, sum(check_methods) as check_methods, sum(instant_methods) as instant_methods, sum(total_earnings) as total_earnings')
                ->with('role', 'company')
                ->orderBy('eow', 'desc')
                ->get()->keyBy(function ($item) {
                    return $item->role_id . '-' . $item->company_id;
                });
            $checkAmounts = PayrollCheckAmount::where('employee_id', $employee->id)->whereBetween('eow', [$startDate, $endDate])->authorizedCompanies('company_id')->groupBy('role_id', 'company_id')->selectRaw('role_id, company_id, sum(amount) as amount, sum(payroll_amount) as payroll_amount, sum(instant_amount) as instant_amount')->get()->keyBy(function ($item) {
                return $item->role_id . '-' . $item->company_id;
            });
            $checks = CheckMaster::where('employee_id', $employee->id)
                ->whereBetween('payroll_eow', [$startDate, $endDate])
                ->authorizedCompanies('company_id')
                ->with([
                    'ledger.ledgerDetails',
                    'company',
                    'fromCompany',
                    'role',
                    'employee',
                    'amReviewer',
                    'hrReviewer',
                    'adminReviewer'
                ])
                ->get()
                ->keyBy(function ($item) {
                    return  $item->company_id;
                });

            foreach ($weeklySummary as $summary) {
                $key = $summary->role_id . '-' . $summary->company_id;
                if (isset($checkAmounts[$key])) {
                    $summary->instant_methods = $checkAmounts[$key]->instant_amount ?? 0;
                    $summary->payroll_methods = $checkAmounts[$key]->payroll_amount ?? 0;
                    $summary->check_methods = $checkAmounts[$key]->amount ?? 0;
                    $summary->total_earnings = $summary->payroll_methods + $summary->check_methods + $summary->instant_methods;
                }
                $checkKey = $summary->company_id;
                $summary->check_data = isset($checks[$checkKey]) ? $checks[$checkKey] : null;
                $collection[] = $summary;
            }
        }

        return to_json([
            'saved' => true,
            'message' => 'Payroll info fetched successfully.',
            'data' => $collection,
        ]);
    }
}
