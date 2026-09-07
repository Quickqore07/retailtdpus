<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Onboarding\EmployeeConfirmation;
use App\Models\Onboarding\EmployeeDocument;
use App\Models\Onboarding\ManualI9;
use App\Models\Payroll\EmployeeRates;
use App\Models\Settings\Company;
use App\Models\Settings\EmployeeRoles;
use App\Models\User;
use App\Services\EmployeeConfirmationExportService;
use App\Services\FileUploadService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmployeeConfirmationController extends Controller
{
    public const EXPORT_TABS = [
        'total' => 'Total Employees',
        'currently_working' => 'Currently Working',
        'verified' => 'Verified Employee',
        'unverified' => 'Unverified Employee',
        'left' => 'Left - Terminated',
        'pending_i9_upload' => 'Pending I-9 Upload',
        'incorrect_i9' => 'Incorrect I9',
    ];

    public function index(Request $request)
    {
        $this->authorize('access', 'manual-i9.index');

        $status = $request->input('status');
        $userId = $request->input('user_id') ? (int) $request->input('user_id') : null;
        $companyIds = $this->resolveCompanyIdsForUser($userId);
        $statusCounts = $this->buildManualI9StatusCounts($companyIds);

        $query = $this->buildManualI9ListQuery($companyIds, $status);

        $collection = $query->filter();

        if (method_exists($collection, 'through')) {
            $collection = $collection->through(fn(ManualI9 $manualI9) => $this->formatManualI9Item($manualI9));
        }

        return to_json([
            'collection' => $collection,
            'status_counts' => $statusCounts,
        ]);
    }

    public function export(Request $request, EmployeeConfirmationExportService $exportService): BinaryFileResponse
    {
        $this->authorize('access', 'manual-i9.index');

        $userId = $request->input('user_id') ? (int) $request->input('user_id') : null;
        $companyIds = $this->resolveCompanyIdsForUser($userId);
        $params = $request->all();
        $status = $request->input('status', 'total');
        unset($params['status']);

        if (!array_key_exists($status, self::EXPORT_TABS)) {
            $status = 'total';
        }

        $query = $this->buildManualI9ListQuery($companyIds, $status);
        $items = $query->export($params);

        $rows = $items->map(fn(ManualI9 $manualI9) => $this->formatManualI9ExportRow($manualI9))->all();

        if (empty($rows)) {
            abort(422, 'No data to export.');
        }

        return $exportService->download([
            [
                'title' => self::EXPORT_TABS[$status],
                'rows' => $rows,
            ],
        ]);
    }

    protected function buildManualI9ListQuery(?array $companyIds, ?string $status = null)
    {
        $i9FormType = EmployeeDocument::DOCUMENT_TYPE_I9_FORM;

        $query = ManualI9::query()
            ->join('employee', 'manual_i9.employee_id', '=', 'employee.id')
            ->join('company', 'manual_i9.company_id', '=', 'company.id')
            ->select('manual_i9.*', 'employee.pos_name as employee_name', 'company.name as company_name')
            ->selectRaw(
                "EXISTS (
                    SELECT 1 FROM employee_documents
                    WHERE employee_documents.employee_id = manual_i9.employee_id
                      AND employee_documents.document_type = ?
                ) as i9_uploaded",
                [$i9FormType]
            )
            ->authorizedWorkgroup('employee.workgroup_id')
            ->with([
                'employee:id,employee_id,pos_name,email,company_id,first_name,last_name,middle_name,street,apt_number,city,state,zip,dob,ssn,phone,hire_date',
                'employee.employeeDocuments' => function ($query) {
                    $query->whereIn('document_type', [
                        EmployeeDocument::DOCUMENT_TYPE_I9_FORM,
                        EmployeeDocument::DOCUMENT_TYPE_I9_FORM_UNSIGNED,
                    ])->select('id', 'employee_id', 'document_type', 'document_name', 'document_path');
                },
                'company:id,name,store_number',
                'employeeConfirmation.reviewer:id,name',
                'employeeConfirmation.authorizer:id,name',
            ]);

        $this->applyCompanyScope($query, $companyIds);
        $this->applyManualI9StatusScope($query, $status);
        $this->applyManualI9Search($query);

        return $query;
    }

    protected function formatManualI9ExportRow(ManualI9 $manualI9): array
    {
        $confirmation = $manualI9->employeeConfirmation;
        $status = $this->resolveEffectiveStatus($manualI9, $confirmation);
        $storeNumber = $manualI9->company?->store_number;
        $storeName = $manualI9->company?->name;

        return [
            $manualI9->employee?->pos_name ?? '',
            $manualI9->employee?->employee_id ?? '',
            $storeNumber !== null && $storeNumber !== '' ? (string) $storeNumber : '',
            $storeName ?? '',
            $this->formatStatusLabel($status),
            $this->formatHrStatusLabel($confirmation?->hr_status ?? EmployeeConfirmation::HR_STATUS_PENDING),
            $confirmation?->i9_choice ?? '',
            $this->formatExportDateTime($confirmation?->approved_at),
            ($manualI9->employee?->employeeDocuments?->contains('document_type', EmployeeDocument::DOCUMENT_TYPE_I9_FORM) ?? false) ? 'Uploaded' : 'Pending',
        ];
    }

    protected function formatStatusLabel(?string $status): string
    {
        return match ($status) {
            EmployeeConfirmation::STATUS_CURRENTLY_WORKING => 'Currently Working',
            EmployeeConfirmation::STATUS_VERIFIED => 'Verified',
            EmployeeConfirmation::STATUS_UNVERIFIED => 'Unverified',
            EmployeeConfirmation::STATUS_LEFT => 'Left / Terminated',
            'total' => 'Total',
            EmployeeConfirmation::TAB_PENDING_I9_UPLOAD => 'Pending I-9 Upload',
            EmployeeConfirmation::TAB_INCORRECT_I9 => 'Incorrect I9',
            default => $status ?: 'N/A',
        };
    }

    protected function formatHrStatusLabel(?string $status): string
    {
        return match ($status) {
            EmployeeConfirmation::HR_STATUS_DOCUMENT_UPLOADED => 'Document Uploaded',
            EmployeeConfirmation::HR_STATUS_REVIEWED => 'Reviewed',
            EmployeeConfirmation::HR_STATUS_AUTHORISED => 'Authorised',
            EmployeeConfirmation::HR_STATUS_PENDING => 'Pending',
            EmployeeConfirmation::HR_STATUS_REJECTED => 'Rejected',
            EmployeeConfirmation::HR_STATUS_TNC => 'TNC',
            EmployeeConfirmation::HR_STATUS_INCORRECT_I9 => 'Incorrect I9',
            default => $status ?: 'N/A',
        };
    }

    protected function formatExportDateTime($value): string
    {
        if (!$value) {
            return '';
        }

        return Carbon::parse($value)->format('M j, Y g:i A');
    }

    protected function resolveCompanyIdsForUser(?int $userId): ?array
    {
        if (!$userId) {
            return null;
        }

        $user = User::find($userId);

        if (!$user) {
            return [];
        }

        return $user->getCompaniesArrayAttribute();
    }

    protected function applyCompanyScope($query, ?array $companyIds): void
    {
        if ($companyIds === null) {
            return;
        }

        if (empty($companyIds)) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn($query->getModel()->getTable() . '.company_id', $companyIds);
    }

    protected function buildManualI9StatusCounts(?array $companyIds = null): array
    {
        return [
            'total' => $this->scopedManualI9Query($companyIds)->count(),
            'currently_working' => $this->countActiveManualI9RowsByConfirmationStatus(
                EmployeeConfirmation::STATUS_CURRENTLY_WORKING,
                $companyIds
            ),
            'verified' => $this->countActiveManualI9RowsByConfirmationStatus(
                EmployeeConfirmation::STATUS_VERIFIED,
                $companyIds
            ),
            'unverified' => $this->countActiveManualI9RowsByConfirmationStatus(
                EmployeeConfirmation::STATUS_UNVERIFIED,
                $companyIds
            ),
            'left' => $this->scopedManualI9Query($companyIds)->where('left_terminate', true)->count(),
            'pending_i9_upload' => $this->countPendingI9UploadManualI9Rows($companyIds),
            'incorrect_i9' => $this->countIncorrectI9ManualI9Rows($companyIds),
        ];
    }

    protected function scopedManualI9Query(?array $companyIds = null)
    {
        $query = ManualI9::join('employee', 'manual_i9.employee_id', '=', 'employee.id')
            ->authorizedWorkgroup('employee.workgroup_id')
            ->select('manual_i9.*');
        $this->applyCompanyScope($query, $companyIds);

        return $query;
    }

    protected function countPendingI9UploadManualI9Rows(?array $companyIds = null): int
    {
        return $this->scopedManualI9Query($companyIds)
            ->where('left_terminate', false)
            ->whereHas('employeeConfirmation', function ($confirmationQuery) {
                $confirmationQuery->where('hr_status', EmployeeConfirmation::HR_STATUS_AUTHORISED);
            })
            ->whereHas('employee', function ($employeeQuery) {
                $employeeQuery->whereDoesntHave('employeeDocuments', function ($docQuery) {
                    $docQuery->where('document_type', EmployeeDocument::DOCUMENT_TYPE_I9_FORM);
                });
            })
            ->count();
    }

    protected function countIncorrectI9ManualI9Rows(?array $companyIds = null): int
    {
        return $this->scopedManualI9Query($companyIds)
            ->where('left_terminate', false)
            ->whereHas('employeeConfirmation', function ($confirmationQuery) {
                $confirmationQuery->where('hr_status', EmployeeConfirmation::HR_STATUS_INCORRECT_I9);
            })
            ->count();
    }

    protected function countActiveManualI9RowsByConfirmationStatus(string $status, ?array $companyIds = null): int
    {
        $query = $this->scopedManualI9Query($companyIds);

        if ($status === EmployeeConfirmation::STATUS_UNVERIFIED) {
            return $query->where('left_terminate', false)->where(function ($builder) {
                $builder->whereDoesntHave('employeeConfirmation')
                    ->orWhereHas('employeeConfirmation', function ($confirmationQuery) {
                        $confirmationQuery->where('status', EmployeeConfirmation::STATUS_UNVERIFIED);
                    });
            })->count();
        } else if ($status === EmployeeConfirmation::STATUS_CURRENTLY_WORKING) {
            return $query->whereHas('employeeConfirmation', function ($confirmationQuery) {
                $confirmationQuery->where('left_terminate', false)->where('status', '!=', EmployeeConfirmation::STATUS_LEFT);
            })->count();
        }

        return $query->whereHas('employeeConfirmation', function ($confirmationQuery) use ($status) {
            $confirmationQuery->where('status', $status);
        })->count();
    }

    protected function applyManualI9StatusScope($query, ?string $status): void
    {
        if (!$status || $status === 'total') {
            return;
        }

        if ($status === EmployeeConfirmation::STATUS_LEFT) {
            $query->where('manual_i9.left_terminate', true);

            return;
        }


        if ($status === EmployeeConfirmation::STATUS_UNVERIFIED) {
            $query->where('manual_i9.left_terminate', false)->where(function ($builder) {
                $builder->whereDoesntHave('employeeConfirmation')
                    ->orWhereHas('employeeConfirmation', function ($confirmationQuery) {
                        $confirmationQuery->where('status', EmployeeConfirmation::STATUS_UNVERIFIED);
                    });
            });

            return;
        }


        if ($status === EmployeeConfirmation::STATUS_CURRENTLY_WORKING) {
            $query->where('manual_i9.left_terminate', false)->whereHas('employeeConfirmation', function ($confirmationQuery) {
                $confirmationQuery->where('left_terminate', false)->where('status', '!=', EmployeeConfirmation::STATUS_LEFT);
            });

            return;
        }

        if ($status === EmployeeConfirmation::TAB_PENDING_I9_UPLOAD) {
            $query->where('manual_i9.left_terminate', false)
                ->whereHas('employeeConfirmation', function ($confirmationQuery) {
                    $confirmationQuery->where('hr_status', EmployeeConfirmation::HR_STATUS_AUTHORISED);
                })
                ->whereHas('employee', function ($employeeQuery) {
                    $employeeQuery->whereDoesntHave('employeeDocuments', function ($docQuery) {
                        $docQuery->where('document_type', EmployeeDocument::DOCUMENT_TYPE_I9_FORM);
                    });
                });

            return;
        }

        if ($status === EmployeeConfirmation::TAB_INCORRECT_I9) {
            $query->where('manual_i9.left_terminate', false)
                ->whereHas('employeeConfirmation', function ($confirmationQuery) {
                    $confirmationQuery->where('hr_status', EmployeeConfirmation::HR_STATUS_INCORRECT_I9);
                });

            return;
        }


        $query->whereHas('employeeConfirmation', function ($confirmationQuery) use ($status) {
            $confirmationQuery->where('status', $status);
        });
    }

    protected function assertManualI9NotTerminated(ManualI9 $manualI9): void
    {
        if ($manualI9->left_terminate) {
            throw ValidationException::withMessages([
                'left_terminate' => ['This employee is marked as left / terminated for this company.'],
            ]);
        }
    }

    protected function assertConfirmationNotAuthorised(EmployeeConfirmation $confirmation): void
    {
        if ($confirmation->hr_status === EmployeeConfirmation::HR_STATUS_AUTHORISED) {
            throw ValidationException::withMessages([
                'hr_status' => ['This employee is authorised and cannot be edited. Unauthorise first to make changes.'],
            ]);
        }
    }

    protected function resolveEffectiveStatus(ManualI9 $manualI9, ?EmployeeConfirmation $confirmation): string
    {
        if ($manualI9->left_terminate) {
            return EmployeeConfirmation::STATUS_LEFT;
        }

        return $confirmation?->status ?? EmployeeConfirmation::STATUS_UNVERIFIED;
    }

    protected function applyManualI9Search($query): void
    {
        $search = request('search');
        if (!$search) {
            return;
        }

        $query->where(function ($builder) use ($search) {
            $builder->whereHas('employee', function ($employeeQuery) use ($search) {
                $employeeQuery->where('pos_name', 'like', '%' . $search . '%')
                    ->orWhere('employee_id', 'like', '%' . $search . '%');
            })->orWhereHas('company', function ($companyQuery) use ($search) {
                $companyQuery->where('name', 'like', '%' . $search . '%')
                    ->orWhere('store_number', 'like', '%' . $search . '%');
            });
        });
    }

    protected function findManualI9OrFail($id): ManualI9
    {
        return ManualI9::query()->findOrFail($id);
    }

    protected function findOrCreateConfirmationForEmployee(int $employeeId): EmployeeConfirmation
    {
        return EmployeeConfirmation::firstOrCreate(
            ['employee_id' => $employeeId],
            [
                'status' => EmployeeConfirmation::STATUS_UNVERIFIED,
                'hr_status' => EmployeeConfirmation::HR_STATUS_PENDING,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]
        );
    }

    protected function resolveConfirmationFromManualI9($manualI9Id): EmployeeConfirmation
    {
        $manualI9 = $this->findManualI9OrFail($manualI9Id);

        return $this->findOrCreateConfirmationForEmployee((int) $manualI9->employee_id);
    }

    protected function formatManualI9Item(ManualI9 $manualI9): array
    {
        $manualI9->loadMissing([
            'employee.employeeDocuments' => function ($query) {
                $query->whereIn('document_type', [
                    EmployeeDocument::DOCUMENT_TYPE_I9_FORM,
                    EmployeeDocument::DOCUMENT_TYPE_I9_FORM_UNSIGNED,
                ])->select('id', 'employee_id', 'document_type', 'document_name', 'document_path');
            },
            'employee:id,employee_id,pos_name,email,company_id,first_name,last_name,middle_name,street,apt_number,city,state,zip,dob,ssn,phone,hire_date',
            'company:id,name,store_number',
            'leftUpdatedBy:id,name',
            'employeeConfirmation.reviewer:id,name',
            'employeeConfirmation.authorizer:id,name',
            'employeeConfirmation.listADocument',
            'employeeConfirmation.listBDocument',
            'employeeConfirmation.listCDocument',
            'employeeConfirmation.authorizationDocument',
            'employeeConfirmation.tncDocument',
        ]);

        $confirmation = $manualI9->employeeConfirmation;
        $formattedConfirmation = $confirmation
            ? $this->formatConfirmation($confirmation)
            : null;

        $documents = $manualI9->employee?->employeeDocuments;
        $I9Document = $documents?->firstWhere('document_type', EmployeeDocument::DOCUMENT_TYPE_I9_FORM);
        $unsignedI9Document = $documents?->firstWhere('document_type', EmployeeDocument::DOCUMENT_TYPE_I9_FORM_UNSIGNED);

        return [
            'id' => $manualI9->id,
            'employee_id' => $manualI9->employee_id,
            'company_id' => $manualI9->company_id,
            'employee' => $manualI9->employee,
            'company' => $manualI9->company,
            'left_terminate' => (bool) $manualI9->left_terminate,
            'left_note' => $manualI9->left_note,
            'left_at' => $manualI9->left_at,
            'left_updated_by' => $manualI9->left_updated_by,
            'left_updated_by_user' => $manualI9->leftUpdatedBy,
            'status' => $this->resolveEffectiveStatus($manualI9, $confirmation),
            'hr_status' => $formattedConfirmation?->hr_status ?? EmployeeConfirmation::HR_STATUS_PENDING,
            'i9_choice' => $formattedConfirmation?->i9_choice,
            'uscis_number' => $formattedConfirmation?->uscis_number,
            'work_authorization_exp_date' => $formattedConfirmation?->work_authorization_exp_date,
            'list_a_doc_type' => $formattedConfirmation?->list_a_doc_type,
            'list_a_issuing_authority' => $formattedConfirmation?->list_a_issuing_authority,
            'list_a_document_number' => $formattedConfirmation?->list_a_document_number,
            'list_a_expiration_date' => $formattedConfirmation?->list_a_expiration_date,
            'list_b_doc_type' => $formattedConfirmation?->list_b_doc_type,
            'list_b_issuing_authority' => $formattedConfirmation?->list_b_issuing_authority,
            'list_b_document_number' => $formattedConfirmation?->list_b_document_number,
            'list_b_expiration_date' => $formattedConfirmation?->list_b_expiration_date,
            'list_c_doc_type' => $formattedConfirmation?->list_c_doc_type,
            'list_c_issuing_authority' => $formattedConfirmation?->list_c_issuing_authority,
            'list_c_document_number' => $formattedConfirmation?->list_c_document_number,
            'list_c_expiration_date' => $formattedConfirmation?->list_c_expiration_date,
            'list_a_doc_id' => $formattedConfirmation?->list_a_doc_id,
            'list_b_doc_id' => $formattedConfirmation?->list_b_doc_id,
            'list_c_doc_id' => $formattedConfirmation?->list_c_doc_id,
            'review_notes' => $formattedConfirmation?->review_notes,
            'reviewed_by' => $formattedConfirmation?->reviewed_by,
            'authorized_by' => $formattedConfirmation?->authorized_by,
            'approved_at' => $formattedConfirmation?->approved_at,
            'authorization_doc_id' => $formattedConfirmation?->authorization_doc_id,
            'tnc_doc_type' => $formattedConfirmation?->tnc_doc_type,
            'tnc_document_id' => $formattedConfirmation?->tnc_document_id,
            'reviewer' => $formattedConfirmation?->reviewer,
            'authorizer' => $formattedConfirmation?->authorizer,
            'list_a_url' => $formattedConfirmation?->list_a_url,
            'list_b_url' => $formattedConfirmation?->list_b_url,
            'list_c_url' => $formattedConfirmation?->list_c_url,
            'authorization_doc_url' => $formattedConfirmation?->authorization_doc_url,
            'tnc_document_url' => $formattedConfirmation?->tnc_document_url,
            'list_a_document' => $formattedConfirmation?->listADocument,
            'list_b_document' => $formattedConfirmation?->listBDocument,
            'list_c_document' => $formattedConfirmation?->listCDocument,
            'has_i9_form' => $I9Document ? true : false,
            'i9_document' => $I9Document,
            'has_i9_without_signatures' => $unsignedI9Document ? true : false,
            'i9_without_signatures_document' => $unsignedI9Document,
            'created_at' => $manualI9->created_at,
            'updated_at' => $manualI9->updated_at,
        ];
    }

    public function show($id)
    {
        $this->authorize('access', 'manual-i9.index');

        $manualI9 = $this->findManualI9OrFail($id);

        return to_json([
            'model' => $this->formatManualI9Item($manualI9),
        ]);
    }

    public function availableEmployees(Request $request)
    {
        $this->authorize('access', 'manual-i9.create');

        $companyId = $request->query('company_id');
        $search = $request->query('query');

        if (!$companyId) {
            return to_json(['collection' => []]);
        }

        $existingIds = ManualI9::query()
            ->where('company_id', $companyId)
            ->pluck('employee_id');

        $employees = Employee::query()
            ->where('active', 1)
            ->where('workgroup_id', session('workgroup'))
            ->whereNotIn('id', $existingIds)
            ->where(function ($query) use ($companyId) {
                $query->whereHas('employeeRates', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })->orWhereHas('employeeRatesRequests', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                });
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('pos_name', 'like', '%' . $search . '%')
                        ->orWhere('employee_id', 'like', '%' . $search . '%');
                });
            })
            ->selectRaw('id, CONCAT(employee_id, " - ", pos_name) as pos_name, employee_id, email')
            ->orderBy('pos_name')
            ->get();

        return to_json([
            'collection' => $employees,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'manual-i9.create');

        $data = $request->validate([
            'company_id' => 'required|integer|exists:company,id',
            'employee_ids' => 'required|array|min:1',
            'employee_ids.*' => 'integer|exists:employee,id',
        ]);

        $companyId = (int) $data['company_id'];
        $employeeIds = array_values(array_unique(array_map('intval', $data['employee_ids'])));

        $eligibleIds = Employee::query()
            ->where('active', 1)
            ->where('workgroup_id', session('workgroup'))
            ->whereIn('id', $employeeIds)
            ->where(function ($query) use ($companyId) {
                $query->whereHas('employeeRates', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })->orWhereHas('employeeRatesRequests', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                });
            })
            ->pluck('id')
            ->all();

        if (count($eligibleIds) !== count($employeeIds)) {
            throw ValidationException::withMessages([
                'employee_ids' => ['One or more selected employees are not valid for the selected company.'],
            ]);
        }

        $existingIds = ManualI9::query()
            ->where('company_id', $companyId)
            ->whereIn('employee_id', $employeeIds)
            ->pluck('employee_id')
            ->all();

        $toCreate = array_values(array_diff($employeeIds, $existingIds));

        if (empty($toCreate)) {
            throw ValidationException::withMessages([
                'employee_ids' => ['Selected employees are already in the confirmation list for this company.'],
            ]);
        }

        $now = now();
        $userId = Auth::id();

        DB::transaction(function () use ($toCreate, $companyId, $now, $userId) {
            $manualRows = array_map(fn($employeeId) => [
                'employee_id' => $employeeId,
                'company_id' => $companyId,
                'created_at' => $now,
                'updated_at' => $now,
            ], $toCreate);

            DB::table('manual_i9')->insert($manualRows);

            foreach ($toCreate as $employeeId) {
                EmployeeConfirmation::firstOrCreate(
                    ['employee_id' => $employeeId],
                    [
                        'status' => EmployeeConfirmation::STATUS_UNVERIFIED,
                        'hr_status' => EmployeeConfirmation::HR_STATUS_PENDING,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        });

        return to_json([
            'saved' => true,
            'message' => count($toCreate) . ' employee(s) added successfully.',
            'added_count' => count($toCreate),
            'skipped_count' => count($existingIds),
        ]);
    }

    public function createEmployee(Request $request)
    {
        $this->authorize('access', 'manual-i9.create');

        $data = $request->validate([
            'last_name' => 'required|string|max:255',
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:10',
            'employee_id' => 'required|string|max:50',
            'pos_name' => 'nullable|string|max:255',
            'ssn' => 'nullable|string|max:20',
            'street' => 'nullable|string|max:255',
            'apt_number' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:50',
            'zip' => 'nullable|string|max:20',
            'dob' => 'nullable|date',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'hire_date' => 'required|date',
            'company_id' => 'required|integer|exists:company,id',
            'role_id' => 'required|integer|exists:employee_roles,id',
        ]);

        $workgroupId = session('workgroup');
        if (!$workgroupId) {
            throw ValidationException::withMessages([
                'company_id' => ['Workgroup is required.'],
            ]);
        }

        $authorizedCompanies = authorizedCompanies();
        if (!in_array((int) $data['company_id'], array_map('intval', $authorizedCompanies), true)) {
            throw ValidationException::withMessages([
                'company_id' => ['You are not authorized for the selected store.'],
            ]);
        }

        $company = Company::query()
            ->select('id', 'name', 'store_number', 'workgroup_id')
            ->findOrFail($data['company_id']);

        if (!empty($data['role_id'])) {
            EmployeeRoles::query()->findOrFail($data['role_id']);
        }

        $customEmployeeId = isset($data['employee_id']) ? trim((string) $data['employee_id']) : '';

        $lastName = trim($data['last_name']);
        $firstName = trim($data['first_name']);
        $middleName = isset($data['middle_name']) ? trim((string) $data['middle_name']) : '';
        $customPosName = isset($data['pos_name']) ? trim((string) $data['pos_name']) : '';
        $posName = $customPosName !== ''
            ? $customPosName
            : trim($lastName . ', ' . $firstName . ($middleName !== '' ? ' ' . strtoupper(substr($middleName, 0, 1)) : ''));
        $checkName = trim($firstName . ($middleName !== '' ? ' ' . strtoupper(substr($middleName, 0, 1)) : '') . ' ' . $lastName);

        $employeeType = $this->resolveEmployeeTypeForCreate($workgroupId, $customEmployeeId, $posName);

        $employee = DB::transaction(function () use ($data, $workgroupId, $company, $lastName, $firstName, $middleName, $posName, $checkName, $customEmployeeId, $employeeType) {
            $employeeId = $customEmployeeId !== '' ? $customEmployeeId : null;

            $employee = Employee::create([
                'employee_id' => $employeeId,
                'pos_name' => $posName,
                'check_name' => $checkName,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'middle_name' => $middleName !== '' ? strtoupper(substr($middleName, 0, 1)) : null,
                'ssn' => $data['ssn'] ?? null,
                'street' => $data['street'],
                'apt_number' => $data['apt_number'] ?? null,
                'city' => $data['city'],
                'state' => $data['state'],
                'zip' => $data['zip'] ?? null,
                'dob' => $data['dob'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'hire_date' => $data['hire_date'],
                'company_id' => $company->id,
                'workgroup_id' => $workgroupId,
                'active' => true,
                'employee_type' => $employeeType,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            EmployeeRates::create([
                'employee_id' => $employee->id,
                'company_id' => $company->id,
                'role_id' => $data['role_id'] ?? 0,
                'pay_type' => 'HR',
                'rate_type' => 'Payroll Regular',
                'payroll_type' => 'Direct Deposit',
                'rate' => 0,
                'ten99_rate' => 0,
                'slab_first_hours' => 0,
                'slab_rest_rate' => 0,
                'payroll_rate' => 0,
                'effective_date' => $data['hire_date'],
                'till_date' => null,
                'payroll_hours_type' => 'fixed',
                'payroll_hours' => 0,
                'check_payment_type' => 'fixed',
                'check_payment_amount' => 0,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            ManualI9::create([
                'employee_id' => $employee->id,
                'company_id' => $company->id,
            ]);

            EmployeeConfirmation::firstOrCreate(
                ['employee_id' => $employee->id],
                [
                    'status' => EmployeeConfirmation::STATUS_UNVERIFIED,
                    'hr_status' => EmployeeConfirmation::HR_STATUS_PENDING,
                    'created_by' => Auth::id(),
                    'updated_by' => Auth::id(),
                ]
            );

            return $employee;
        });

        $employee->load('company');

        $companyName = $employee->company?->name;
        if ($employee->company && $employee->company->store_number && !str_contains((string) $companyName, (string) $employee->company->store_number)) {
            $companyName = trim($employee->company->store_number . ' - ' . ($employee->company->name ?? ''));
        }

        $displayName = $employee->employee_id . ' - ' . $employee->pos_name;

        return to_json([
            'saved' => true,
            'success' => true,
            'message' => 'Employee created and added to confirmation list.',
            'data' => [
                'id' => $employee->id,
                'employee_id' => $employee->employee_id,
                'pos_name' => $displayName,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'email' => $employee->email,
                'company_id' => $employee->company_id,
                'company' => $employee->company
                    ? [
                        'id' => $employee->company->id,
                        'name' => $companyName,
                        'store_number' => $employee->company->store_number,
                    ]
                    : null,
            ],
        ]);
    }

    /**
     * Match ID/name the same way employee hours upload does:
     * ID + POS/alias name => already exists (error);
     * ID or name only => Existing; otherwise New.
     */
    protected function resolveEmployeeTypeForCreate(int $workgroupId, string $employeeId, string $posName): string
    {
        $normalizeName = static function (?string $name): string {
            return strtolower(preg_replace('/\s+/', ' ', trim((string) $name)));
        };

        $normalizedIncomingName = $normalizeName($posName);
        $idExists = false;
        $nameExists = false;

        $existingEmployees = Employee::query()
            ->with('aliases')
            ->select('id', 'employee_id', 'pos_name')
            ->where('workgroup_id', $workgroupId)
            ->get();

        foreach ($existingEmployees as $existingEmployee) {
            $idMatch = $employeeId !== '' && in_array($employeeId, array_map('strval', $existingEmployee->allMatchIds()), true);
            $nameMatch = $normalizedIncomingName !== '' && in_array(
                $normalizedIncomingName,
                array_map($normalizeName, $existingEmployee->allMatchNames()),
                true
            );

            if ($idMatch && $nameMatch) {
                throw ValidationException::withMessages([
                    'employee_id' => ['This employee already exists.'],
                    'pos_name' => ['This employee already exists.'],
                ]);
            }

            $idExists = $idExists || $idMatch;
            $nameExists = $nameExists || $nameMatch;
        }

        return ($idExists || $nameExists) ? 'Existing' : 'New';
    }

    protected function generateUniqueEmployeeId(int $workgroupId): string
    {
        do {
            $employeeId = 'EC' . strtoupper(Str::random(8));
            $exists = Employee::query()
                ->where('workgroup_id', $workgroupId)
                ->where(function ($query) use ($employeeId) {
                    $query->where('employee_id', $employeeId)
                        ->orWhereHas('aliases', fn($a) => $a->where('alias_employee_id', $employeeId));
                })
                ->exists();
        } while ($exists);

        return $employeeId;
    }

    protected function manualI9Response(ManualI9 $manualI9, string $message, bool $saved = true): array
    {
        return [
            'saved' => $saved,
            'message' => $message,
            'model' => $this->formatManualI9Item($manualI9),
        ];
    }

    public function updateEmployee(Request $request, $id)
    {
        $this->authorize('access', 'manual-i9.update');

        $manualI9 = $this->findManualI9OrFail($id);
        $employee = $manualI9->employee;

        if (!$employee) {
            abort(404, 'Employee not found.');
        }

        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:10',
            'last_name' => 'required|string|max:255',
            'street' => 'required|string|max:255',
            'apt_number' => 'nullable|string|max:50',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:50',
            'zip' => 'nullable|string|max:20',
            'dob' => 'nullable|date',
            'ssn' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'hire_date' => 'required|date',
        ]);

        $lastName = trim($data['last_name']);
        $firstName = trim($data['first_name']);
        $middleName = isset($data['middle_name']) ? trim((string) $data['middle_name']) : '';
        $posName = trim($lastName . ', ' . $firstName . ($middleName !== '' ? ' ' . strtoupper(substr($middleName, 0, 1)) : ''));
        $checkName = trim($firstName . ($middleName !== '' ? ' ' . strtoupper(substr($middleName, 0, 1)) : '') . ' ' . $lastName);

        $employee->fill([
            'first_name' => $firstName,
            'middle_name' => $middleName !== '' ? strtoupper(substr($middleName, 0, 1)) : null,
            'last_name' => $lastName,
            'pos_name' => $posName,
            'check_name' => $checkName,
            'street' => $data['street'],
            'apt_number' => $data['apt_number'] ?? null,
            'city' => $data['city'],
            'state' => $data['state'],
            'zip' => $data['zip'] ?? null,
            'dob' => $data['dob'] ?? null,
            'ssn' => $data['ssn'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'hire_date' => $data['hire_date'],
        ]);
        $employee->updated_by = Auth::id();
        $employee->save();

        return to_json($this->manualI9Response($manualI9->fresh(), 'Employee details updated successfully.'));
    }

    public function submitStep1(Request $request, $id)
    {
        $this->authorize('access', 'manual-i9.update');

        $manualI9 = $this->findManualI9OrFail($id);
        $confirmation = $this->resolveConfirmationFromManualI9($id);

        $this->assertManualI9NotTerminated($manualI9);
        $this->assertConfirmationNotAuthorised($confirmation);

        $data = $request->validate([
            'i9_choice' => 'required|string|in:citizen,national,lpr,alien',
            'uscis_number' => 'nullable|string|max:100',
            'work_authorization_exp_date' => 'nullable|date',
        ]);

        if ($data['i9_choice'] === 'lpr' && empty($data['uscis_number'])) {
            throw ValidationException::withMessages([
                'uscis_number' => ['USCIS or A-Number is required for lawful permanent residents.'],
            ]);
        }

        if ($data['i9_choice'] === 'alien' && empty($data['uscis_number'])) {
            throw ValidationException::withMessages([
                'uscis_number' => ['Enter one of: USCIS A-Number, Form I-94 number, or Foreign Passport + Country.'],
            ]);
        }

        $confirmation->i9_choice = $data['i9_choice'];
        $confirmation->uscis_number = in_array($data['i9_choice'], ['lpr', 'alien'], true)
            ? ($data['uscis_number'] ?? null)
            : null;
        $confirmation->work_authorization_exp_date = $data['i9_choice'] === 'alien'
            ? ($data['work_authorization_exp_date'] ?? null)
            : null;
        $confirmation->save();

        if ($confirmation->hr_status !== EmployeeConfirmation::HR_STATUS_TNC) {
            $confirmation->hr_status = EmployeeConfirmation::HR_STATUS_DOCUMENT_UPLOADED;
            $confirmation->save();
        }

        return to_json($this->manualI9Response($manualI9, 'I-9 status saved successfully.'));
    }

    public function submitStep2(Request $request, $id)
    {
        $this->authorize('access', 'manual-i9.update');

        $manualI9 = $this->findManualI9OrFail($id);
        $confirmation = $this->resolveConfirmationFromManualI9($id);
        $confirmation->load('employee:id,pos_name');

        $this->assertManualI9NotTerminated($manualI9);
        $this->assertConfirmationNotAuthorised($confirmation);

        $fileRule = 'nullable|file|' . upload_max_file_size_rule() . '|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx';

        $data = $request->validate([
            'i9_choice' => 'nullable|string|in:citizen,national,lpr,alien',
            'uscis_number' => 'nullable|string|max:100',
            'work_authorization_exp_date' => 'nullable|date',
            'list_a_doc_type' => 'nullable|string|max:20',
            'list_a_issuing_authority' => 'nullable|string|max:255',
            'list_a_document_number' => 'nullable|string|max:100',
            'list_a_expiration_date' => 'nullable|date',
            'list_b_doc_type' => 'nullable|string|max:20',
            'list_b_issuing_authority' => 'nullable|string|max:255',
            'list_b_document_number' => 'nullable|string|max:100',
            'list_b_expiration_date' => 'nullable|date',
            'list_c_doc_type' => 'nullable|string|max:20',
            'list_c_issuing_authority' => 'nullable|string|max:255',
            'list_c_document_number' => 'nullable|string|max:100',
            'list_c_expiration_date' => 'nullable|date',
            'list_a_file' => $fileRule,
            'list_b_file' => $fileRule,
            'list_c_file' => $fileRule,
        ]);

        $i9Choice = $data['i9_choice'] ?? $confirmation->i9_choice;

        if (!$i9Choice) {
            throw ValidationException::withMessages([
                'i9_choice' => ['Please complete I-9 status selection first.'],
            ]);
        }

        if (!empty($data['i9_choice'])) {
            if ($data['i9_choice'] === 'lpr' && empty($data['uscis_number'])) {
                throw ValidationException::withMessages([
                    'uscis_number' => ['USCIS or A-Number is required for lawful permanent residents.'],
                ]);
            }

            if ($data['i9_choice'] === 'alien' && empty($data['uscis_number'])) {
                throw ValidationException::withMessages([
                    'uscis_number' => ['Enter one of: USCIS A-Number, Form I-94 number, or Foreign Passport + Country.'],
                ]);
            }

            $confirmation->i9_choice = $data['i9_choice'];
            $confirmation->uscis_number = in_array($data['i9_choice'], ['lpr', 'alien'], true)
                ? ($data['uscis_number'] ?? null)
                : null;
            $confirmation->work_authorization_exp_date = $data['i9_choice'] === 'alien'
                ? ($data['work_authorization_exp_date'] ?? null)
                : null;
        }

        $listADocType = $request->input('list_a_doc_type') ?? $confirmation->list_a_doc_type;
        $isNoPassportSelected = $listADocType === 'no_passport';
        $isListAOnly = in_array($i9Choice, ['citizen', 'national'], true) && !$isNoPassportSelected;

        if ($isListAOnly) {
            if (empty($request->input('list_a_doc_type')) && empty($confirmation->list_a_doc_type)) {
                throw ValidationException::withMessages([
                    'list_a_doc_type' => ['Please select a List A document type.'],
                ]);
            }
            if (!$request->hasFile('list_a_file') && empty($confirmation->list_a_doc_id)) {
                throw ValidationException::withMessages([
                    'list_a_file' => ['List A document is required for U.S. citizens and noncitizen nationals.'],
                ]);
            }
        } else {
            if (empty($request->input('list_b_doc_type')) && empty($confirmation->list_b_doc_type)) {
                throw ValidationException::withMessages([
                    'list_b_doc_type' => ['Please select a List B document type.'],
                ]);
            }
            if (empty($request->input('list_c_doc_type')) && empty($confirmation->list_c_doc_type)) {
                throw ValidationException::withMessages([
                    'list_c_doc_type' => ['Please select a List C document type.'],
                ]);
            }
            if (!$request->hasFile('list_b_file') && empty($confirmation->list_b_doc_id)) {
                throw ValidationException::withMessages([
                    'list_b_file' => ['List B document is required.'],
                ]);
            }
            if (!$request->hasFile('list_c_file') && empty($confirmation->list_c_doc_id)) {
                throw ValidationException::withMessages([
                    'list_c_file' => ['List C document is required.'],
                ]);
            }
        }

        if ($request->filled('list_a_doc_type')) {
            $confirmation->list_a_doc_type = $request->input('list_a_doc_type');
        }
        if ($request->filled('list_b_doc_type')) {
            $confirmation->list_b_doc_type = $request->input('list_b_doc_type');
        }
        if ($request->filled('list_c_doc_type')) {
            $confirmation->list_c_doc_type = $request->input('list_c_doc_type');
        }

        $confirmation->list_a_issuing_authority = $data['list_a_issuing_authority'] ?? $confirmation->list_a_issuing_authority;
        $confirmation->list_a_document_number = $data['list_a_document_number'] ?? $confirmation->list_a_document_number;
        $confirmation->list_a_expiration_date = $data['list_a_expiration_date'] ?? $confirmation->list_a_expiration_date;
        $confirmation->list_b_issuing_authority = $data['list_b_issuing_authority'] ?? $confirmation->list_b_issuing_authority;
        $confirmation->list_b_document_number = $data['list_b_document_number'] ?? $confirmation->list_b_document_number;
        $confirmation->list_b_expiration_date = $data['list_b_expiration_date'] ?? $confirmation->list_b_expiration_date;
        $confirmation->list_c_issuing_authority = $data['list_c_issuing_authority'] ?? $confirmation->list_c_issuing_authority;
        $confirmation->list_c_document_number = $data['list_c_document_number'] ?? $confirmation->list_c_document_number;
        $confirmation->list_c_expiration_date = $data['list_c_expiration_date'] ?? $confirmation->list_c_expiration_date;

        if ($request->hasFile('list_a_file') || $confirmation->list_a_doc_type) {
            $confirmation->list_a_doc_id = $this->upsertListDocument(
                $confirmation,
                $request->file('list_a_file'),
                'a',
                $request->input('list_a_doc_name') ?? $confirmation->list_a_doc_type,
                $confirmation->list_a_doc_id
            );
        }

        if ($request->hasFile('list_b_file') || $confirmation->list_b_doc_type) {
            $confirmation->list_b_doc_id = $this->upsertListDocument(
                $confirmation,
                $request->file('list_b_file'),
                'b',
                $request->input('list_b_doc_name') ?? $confirmation->list_b_doc_type,
                $confirmation->list_b_doc_id
            );
        }

        if ($request->hasFile('list_c_file') || $confirmation->list_c_doc_type) {
            $confirmation->list_c_doc_id = $this->upsertListDocument(
                $confirmation,
                $request->file('list_c_file'),
                'c',
                $request->input('list_c_doc_name') ?? $confirmation->list_c_doc_type,
                $confirmation->list_c_doc_id
            );
        }

        if ($confirmation->hr_status !== EmployeeConfirmation::HR_STATUS_TNC) {
            $confirmation->hr_status = EmployeeConfirmation::HR_STATUS_DOCUMENT_UPLOADED;
        }

        $confirmation->save();

        return to_json($this->manualI9Response($manualI9, 'I-9 status and documents saved successfully.'));
    }

    public function submitReview(Request $request, $id)
    {
        $this->authorize('access', 'manual-i9.review');

        $manualI9 = $this->findManualI9OrFail($id);
        $confirmation = $this->resolveConfirmationFromManualI9($id);

        $this->assertManualI9NotTerminated($manualI9);
        $this->assertConfirmationNotAuthorised($confirmation);

        if (!$confirmation->i9_choice) {
            throw ValidationException::withMessages([
                'i9_choice' => ['Please complete I-9 status selection first.'],
            ]);
        }

        $isListAOnly = in_array($confirmation->i9_choice, ['citizen', 'national'], true)
            && $confirmation->list_a_doc_type !== 'no_passport';

        if ($isListAOnly && empty($confirmation->list_a_doc_id)) {
            throw ValidationException::withMessages([
                'list_a_doc_id' => ['List A document must be uploaded before review.'],
            ]);
        }

        if (!$isListAOnly && (empty($confirmation->list_b_doc_id) || empty($confirmation->list_c_doc_id))) {
            throw ValidationException::withMessages([
                'documents' => ['List B and List C documents must be uploaded before review.'],
            ]);
        }

        $data = $request->validate([
            'review_notes' => 'nullable|string|max:5000',
        ]);

        $confirmation->review_notes = $data['review_notes'] ?? null;
        $confirmation->reviewed_by = Auth::id();
        $confirmation->hr_status = EmployeeConfirmation::HR_STATUS_REVIEWED;
        $confirmation->save();

        return to_json($this->manualI9Response($manualI9, 'Employee confirmation reviewed successfully.'));
    }

    protected function upsertListDocument(
        EmployeeConfirmation $confirmation,
        $file,
        string $listKey,
        ?string $docType,
        ?int $existingDocId
    ): ?int {
        $documentType = match ($listKey) {
            'a' => EmployeeDocument::DOCUMENT_TYPE_LIST_A,
            'b' => EmployeeDocument::DOCUMENT_TYPE_LIST_B,
            'c' => EmployeeDocument::DOCUMENT_TYPE_LIST_C,
            default => EmployeeDocument::DOCUMENT_TYPE_OTHER,
        };

        $documentName = $docType ? "list_{$listKey}_{$docType}" : "list_{$listKey}";
        $document = $existingDocId ? EmployeeDocument::find($existingDocId) : null;

        if ($file) {
            $fileService = app(FileUploadService::class);
            $uploadDir = 'employee-documents/' . $confirmation->employee_id;
            $result = $fileService->store($file, $uploadDir);

            if ($document) {
                if ($document->document_path) {
                    $fileService->delete($document->document_path);
                }
                $document->document_path = $result['path'];
                $document->document_name = $documentName;
                $document->document_type = $documentType;
                $document->save();
            } else {
                $document = EmployeeDocument::create([
                    'employee_id' => $confirmation->employee_id,
                    'document_name' => $documentName,
                    'document_path' => $result['path'],
                    'document_type' => $documentType,
                ]);
            }
        } elseif ($document && $docType) {
            $document->document_name = $documentName;
            $document->document_type = $documentType;
            $document->save();
        }

        return $document?->id;
    }

    protected function formatConfirmation(EmployeeConfirmation $confirmation): EmployeeConfirmation
    {
        $confirmation->load([
            'employee:id,employee_id,pos_name,email,company_id,first_name,last_name,middle_name,street,apt_number,city,state,zip,dob,ssn,phone,hire_date',
            'employee.company:id,name,store_number',
            'reviewer:id,name',
            'authorizer:id,name',
            'listADocument',
            'listBDocument',
            'listCDocument',
            'authorizationDocument',
            'tncDocument',
        ]);

        $confirmation->list_a_url = $confirmation->listADocument?->document_path_url;
        $confirmation->list_b_url = $confirmation->listBDocument?->document_path_url;
        $confirmation->list_c_url = $confirmation->listCDocument?->document_path_url;
        $confirmation->authorization_doc_url = $confirmation->authorizationDocument?->document_path_url;
        $confirmation->tnc_document_url = $confirmation->tncDocument?->document_path_url;

        return $confirmation;
    }

    public function authorizeEmployee(Request $request, $id)
    {
        $this->authorize('access', 'manual-i9.approve');

        $manualI9 = $this->findManualI9OrFail($id);
        $confirmation = $this->resolveConfirmationFromManualI9($id);

        // $this->assertManualI9NotTerminated($manualI9);

        // if (!$confirmation->reviewed_by) {
        //     throw ValidationException::withMessages([
        //         'reviewed_by' => ['Employee must be reviewed before authorization.'],
        //     ]);
        // }

        // $fileRule = 'nullable|file|' . upload_max_file_size_rule() . '|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx';

        // $request->validate([
        //     'authorization_doc_file' => $fileRule,
        // ]);

        // if (!$request->hasFile('authorization_doc_file') && empty($confirmation->authorization_doc_id)) {
        //     throw ValidationException::withMessages([
        //         'authorization_doc_file' => ['Employment authorization document is required.'],
        //     ]);
        // }

        $fileRule = 'nullable|file|' . upload_max_file_size_rule() . '|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx';

        $request->validate([
            'authorization_doc_file' => $fileRule,
            'tnc_doc_type' => 'nullable|string|max:100',
            'tnc_document_file' => $fileRule,
        ]);

        if ($request->hasFile('authorization_doc_file')) {
            $confirmation->authorization_doc_id = $this->upsertAuthorizationDocument(
                $confirmation,
                $request->file('authorization_doc_file'),
                $confirmation->authorization_doc_id
            );
        }

        if ($request->hasFile('tnc_document_file')) {
            $confirmation->tnc_document_id = $this->upsertTncDocument(
                $confirmation,
                $request->file('tnc_document_file'),
                $request->input('tnc_doc_type'),
                $confirmation->tnc_document_id
            );
        }

        if ($request->filled('tnc_doc_type')) {
            $confirmation->tnc_doc_type = $request->input('tnc_doc_type');
        }

        if (
            $confirmation->hr_status === EmployeeConfirmation::HR_STATUS_TNC
            && empty($confirmation->tnc_document_id)
        ) {
            throw ValidationException::withMessages([
                'tnc_document_file' => ['TNC document is required before authorization.'],
            ]);
        }

        if (
            $confirmation->hr_status === EmployeeConfirmation::HR_STATUS_TNC
            && empty($confirmation->tnc_doc_type)
        ) {
            throw ValidationException::withMessages([
                'tnc_doc_type' => ['TNC document type is required before authorization.'],
            ]);
        }

        $confirmation->authorized_by = Auth::id();
        $confirmation->hr_status = EmployeeConfirmation::HR_STATUS_AUTHORISED;
        $confirmation->approved_at = now();

        $confirmation->status = EmployeeConfirmation::STATUS_VERIFIED;

        $confirmation->save();

        return to_json($this->manualI9Response($manualI9, 'Employee authorized successfully.'));
    }

    public function unauthorizeEmployee(Request $request, $id)
    {
        $this->authorize('access', 'manual-i9.approve');

        $manualI9 = $this->findManualI9OrFail($id);
        $confirmation = $this->resolveConfirmationFromManualI9($id);

        $this->assertManualI9NotTerminated($manualI9);

        if (
            $confirmation->hr_status !== EmployeeConfirmation::HR_STATUS_AUTHORISED
            && $confirmation->hr_status !== EmployeeConfirmation::HR_STATUS_INCORRECT_I9
        ) {
            throw ValidationException::withMessages([
                'hr_status' => ['Only authorised or Incorrect I9 employees can be unauthorised.'],
            ]);
        }

        $confirmation->authorized_by = null;
        $confirmation->approved_at = null;
        $confirmation->hr_status = EmployeeConfirmation::HR_STATUS_REVIEWED;
        $confirmation->status = EmployeeConfirmation::STATUS_UNVERIFIED;
        $confirmation->save();

        return to_json($this->manualI9Response($manualI9, 'Employee unauthorised successfully.'));
    }

    protected function upsertAuthorizationDocument(
        EmployeeConfirmation $confirmation,
        $file,
        ?int $existingDocId
    ): ?int {
        $document = $existingDocId ? EmployeeDocument::find($existingDocId) : null;
        $fileService = app(FileUploadService::class);
        $uploadDir = 'employee-documents/' . $confirmation->employee_id;
        $result = $fileService->store($file, $uploadDir);

        if ($document) {
            if ($document->document_path) {
                $fileService->delete($document->document_path);
            }
            $document->document_path = $result['path'];
            $document->document_name = 'Authorization Document';
            $document->document_type = EmployeeDocument::DOCUMENT_TYPE_AUTHORIZATION;
            $document->save();
        } else {
            $document = EmployeeDocument::create([
                'employee_id' => $confirmation->employee_id,
                'document_name' => 'Authorization Document',
                'document_path' => $result['path'],
                'document_type' => EmployeeDocument::DOCUMENT_TYPE_AUTHORIZATION,
            ]);
        }

        return $document->id;
    }

    protected function upsertTncDocument(
        EmployeeConfirmation $confirmation,
        $file,
        ?string $docType,
        ?int $existingDocId
    ): ?int {
        $document = $existingDocId ? EmployeeDocument::find($existingDocId) : null;
        $fileService = app(FileUploadService::class);
        $uploadDir = 'employee-documents/' . $confirmation->employee_id;
        $documentName = $docType ? 'TNC - ' . $docType : 'TNC Document';

        if ($file) {
            $result = $fileService->store($file, $uploadDir);
            if ($document) {
                if ($document->document_path) {
                    $fileService->delete($document->document_path);
                }
                $document->document_path = $result['path'];
                $document->document_name = $documentName;
                $document->document_type = EmployeeDocument::DOCUMENT_TYPE_TNC;
                $document->save();
            } else {
                $document = EmployeeDocument::create([
                    'employee_id' => $confirmation->employee_id,
                    'document_name' => $documentName,
                    'document_path' => $result['path'],
                    'document_type' => EmployeeDocument::DOCUMENT_TYPE_TNC,
                ]);
            }
        } elseif ($document && $docType) {
            $document->document_name = $documentName;
            $document->save();
        }

        return $document?->id;
    }

    public function reject($id)
    {
        $this->authorize('access', 'manual-i9.review');

        $manualI9 = $this->findManualI9OrFail($id);
        $confirmation = $this->resolveConfirmationFromManualI9($id);

        $this->assertManualI9NotTerminated($manualI9);
        $this->assertConfirmationNotAuthorised($confirmation);

        $confirmation->hr_status = EmployeeConfirmation::HR_STATUS_REJECTED;
        $confirmation->reviewed_by = Auth::id();
        $confirmation->save();

        return to_json($this->manualI9Response($manualI9, 'Employee marked as rejected.'));
    }

    public function markTnc($id)
    {
        $this->authorize('access', 'manual-i9.review');

        $manualI9 = $this->findManualI9OrFail($id);
        $confirmation = $this->resolveConfirmationFromManualI9($id);

        $this->assertManualI9NotTerminated($manualI9);
        $this->assertConfirmationNotAuthorised($confirmation);

        $confirmation->hr_status = EmployeeConfirmation::HR_STATUS_TNC;
        $confirmation->reviewed_by = Auth::id();
        $confirmation->save();

        return to_json($this->manualI9Response($manualI9, 'Employee marked as TNC.'));
    }

    public function markIncorrectI9($id)
    {
        $this->authorize('access', 'manual-i9.approve');

        $manualI9 = $this->findManualI9OrFail($id);
        $confirmation = $this->resolveConfirmationFromManualI9($id);

        $this->assertManualI9NotTerminated($manualI9);

        if (
            $confirmation->hr_status !== EmployeeConfirmation::HR_STATUS_AUTHORISED
            || $confirmation->status !== EmployeeConfirmation::STATUS_VERIFIED
        ) {
            throw ValidationException::withMessages([
                'hr_status' => ['Incorrect I9 can only be set for authorised and verified employees.'],
            ]);
        }

        $confirmation->hr_status = EmployeeConfirmation::HR_STATUS_INCORRECT_I9;
        $confirmation->save();

        return to_json($this->manualI9Response($manualI9, 'Employee marked as Incorrect I9.'));
    }

    public function markLeft(Request $request, $id)
    {
        $this->authorize('access', 'manual-i9.update');

        $manualI9 = $this->findManualI9OrFail($id);

        if ($manualI9->left_terminate) {
            throw ValidationException::withMessages([
                'left_terminate' => ['This employee is already marked as left / terminated for this company.'],
            ]);
        }

        $data = $request->validate([
            'left_note' => 'nullable|string|max:5000',
        ]);

        $manualI9->left_terminate = true;
        $manualI9->left_note = $data['left_note'] ?? null;
        $manualI9->left_at = now();
        $manualI9->left_updated_by = Auth::id();
        $manualI9->save();

        return to_json($this->manualI9Response($manualI9, 'Employee marked as left / terminated for this company.'));
    }

    public function removeLeft($id)
    {
        $this->authorize('access', 'manual-i9.left');

        $manualI9 = $this->findManualI9OrFail($id);

        if (!$manualI9->left_terminate) {
            throw ValidationException::withMessages([
                'left_terminate' => ['This employee is not marked as left / terminated for this company.'],
            ]);
        }

        $manualI9->left_terminate = false;
        $manualI9->left_note = null;
        $manualI9->left_at = null;
        $manualI9->left_updated_by = null;
        $manualI9->save();

        return to_json($this->manualI9Response(
            $manualI9,
            'Employee removed from left / terminated for this company.'
        ));
    }

    public function uploadI9WithoutSignatures(Request $request, $id)
    {
        $this->authorize('access', 'manual-i9.approve');

        $manualI9 = $this->findManualI9OrFail($id);
        $confirmation = $this->resolveConfirmationFromManualI9($id);

        $this->assertManualI9NotTerminated($manualI9);

        $request->validate([
            'file' => 'required|file|' . upload_max_file_size_rule() . '|mimes:pdf,jpg,jpeg,png,doc,docx',
        ]);

        $this->upsertUnsignedI9Document($confirmation, $request->file('file'));

        $confirmation->authorized_by = Auth::id();
        $confirmation->hr_status = EmployeeConfirmation::HR_STATUS_AUTHORISED;
        $confirmation->approved_at = now();
        $confirmation->status = EmployeeConfirmation::STATUS_VERIFIED;
        $confirmation->save();

        return to_json($this->manualI9Response(
            $manualI9->fresh(),
            'I-9 without signatures uploaded. Employee authorised and verified.'
        ));
    }

    public function downloadI9($id)
    {
        $this->authorize('access', 'manual-i9.index');

        $manualI9 = $this->findManualI9OrFail($id);
        $manualI9->loadMissing(['employee', 'company', 'employeeConfirmation.employee']);

        $employeeIdentifier = $manualI9->employee?->employee_id ?? $manualI9->employee_id;
        $filename = 'I9-Form-' . $employeeIdentifier . '-' . now()->format('Y-m-d') . '.pdf';

        $unsignedDocument = EmployeeDocument::query()
            ->where('employee_id', $manualI9->employee_id)
            ->where('document_type', EmployeeDocument::DOCUMENT_TYPE_I9_FORM_UNSIGNED)
            ->latest('id')
            ->first();

        if ($unsignedDocument?->document_path) {
            return $this->downloadStoredDocument($unsignedDocument->document_path, $filename);
        }

        $confirmation = $manualI9->employeeConfirmation
            ?? $this->findOrCreateConfirmationForEmployee((int) $manualI9->employee_id);
        $confirmation->setRelation('employee', $manualI9->employee);

        $pdfOptions = [
            'isRemoteEnabled' => true,
            'isFontSubsettingEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'chroot' => public_path(),
        ];

        $pdf = Pdf::loadView('pdfs.i9-pdf', [
            'employeeConfirmation' => $confirmation,
            'employee' => $manualI9->employee,
            'company' => $manualI9->company,
            'withoutSignatures' => true,
        ])->setOptions($pdfOptions);

        return $pdf->stream($filename);
    }

    protected function upsertUnsignedI9Document(EmployeeConfirmation $confirmation, $file): EmployeeDocument
    {
        $fileService = app(FileUploadService::class);
        $uploadDir = 'employee-documents/' . $confirmation->employee_id;
        $result = $fileService->store($file, $uploadDir);

        $document = EmployeeDocument::query()
            ->where('employee_id', $confirmation->employee_id)
            ->where('document_type', EmployeeDocument::DOCUMENT_TYPE_I9_FORM_UNSIGNED)
            ->latest('id')
            ->first();

        if ($document) {
            if ($document->document_path) {
                $fileService->delete($document->document_path);
            }
            $document->document_path = $result['path'];
            $document->document_name = 'i9_form_without_signatures';
            $document->document_type = EmployeeDocument::DOCUMENT_TYPE_I9_FORM_UNSIGNED;
            $document->save();

            return $document;
        }

        return EmployeeDocument::create([
            'employee_id' => $confirmation->employee_id,
            'document_name' => 'i9_form_without_signatures',
            'document_path' => $result['path'],
            'document_type' => EmployeeDocument::DOCUMENT_TYPE_I9_FORM_UNSIGNED,
        ]);
    }

    protected function downloadStoredDocument(string $path, string $filename)
    {
        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
        $adapter = Storage::disk($disk);

        if (!$adapter->exists($path)) {
            abort(404, 'I-9 document not found.');
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if ($extension) {
            $filename = pathinfo($filename, PATHINFO_FILENAME) . '.' . $extension;
        }

        $mime = $adapter->mimeType($path) ?: 'application/octet-stream';
        $contents = $adapter->get($path);

        return response($contents, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
