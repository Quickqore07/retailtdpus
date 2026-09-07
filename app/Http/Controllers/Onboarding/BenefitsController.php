<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Onboarding\OnboardingList;
use App\Services\MailService;
use App\Support\BenefitElection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class BenefitsController extends Controller
{
    /**
     * Display a listing of employees where benefit mail was sent
     */
    public function index(Request $request)
    {
        $this->authorize('access', 'benefits.index');

        return to_json([
            'collection' => $this->buildBenefitsListQuery()->filter(),
        ]);
    }

    public function export(Request $request)
    {
        $this->authorize('access', 'benefits.index');

        $employees = $this->buildBenefitsListQuery()
            ->export($request->all());

        ini_set('memory_limit', '512M');
        set_time_limit(300);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = $this->getBenefitsExportHeaders();
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
                $this->getBenefitsExportRow($employee),
                null,
                'A' . $row
            );
            $row++;
        }

        for ($i = 1; $i <= count($headers); $i++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'benefits_export_' . date('Y-m-d_His') . '.xlsx';
        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    private function buildBenefitsListQuery()
    {
        return Employee::query()
            ->with([
                'employeeRates.company.state',
                'employeeRatesRequests.company.state',
                'benefitEnrollment:id,employee_id,benefit_acknowledgment,medical_plan,medical_waive_reason,dental,vision,life_option,tax_method',
                'aliases',
            ])
            ->whereNotNull('benefit_mail_sent')
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

    private function getBenefitsExportHeaders(): array
    {
        return [
            'Company Names',
            'Employee ID',
            'POS Name',
            'Alias Employee IDs',
            'Alias Names',
            'Email',
            'Phone Number',
            'SSN',
            'Address',
            'City',
            'State',
            'Zip',
            'Benefits State',
            'Mail Sent Date',
            'Benefit Status',
            'Benefit Submitted Date',
            'Medical Plan',
            'Medical Waive Reason',
            'Dental Plan',
            'Vision Plan',
            'Life Option',
            'Tax Method',
        ];
    }

    private function getBenefitsExportRow(Employee $employee): array
    {
        $enrollment = $employee->benefitEnrollment;

        return [
            $this->formatEmployeeCompanyNames($employee),
            $employee->employee_id ?? '',
            $employee->pos_name ?? '',
            collect($employee->aliasIds())->implode(', '),
            collect($employee->aliasNames())->implode(', '),
            $employee->email ?? '',
            $employee->phone ?? '',
            $employee->ssn ?? '',
            $this->formatEmployeeStreetAddress($employee),
            $employee->city ?? '',
            $employee->state ?? '',
            $employee->zip ?? '',
            $employee->benefits_state ?? '',
            $this->formatExportDate($employee->benefit_mail_sent),
            $this->formatBenefitStatus($employee),
            $this->formatExportDate($employee->benefit_submitted_at),
            $this->formatMedicalPlanLabel($enrollment?->medical_plan),
            $enrollment?->medical_waive_reason ?? '',
            $this->formatCoveragePlanLabel($enrollment?->dental),
            $this->formatCoveragePlanLabel($enrollment?->vision),
            $this->formatLifeOptionLabel($enrollment?->life_option),
            $this->formatTaxMethodLabel($enrollment?->tax_method),
        ];
    }

    private function formatEmployeeStreetAddress(Employee $employee): string
    {
        $street = trim((string) ($employee->street ?? ''));

        if ($employee->apt_number) {
            $street = trim($street . ' Apt ' . $employee->apt_number);
        }

        return $street;
    }

    private function formatMedicalPlanLabel(?string $value): string
    {
        return match ($value) {
            'option1_employee' => 'Option 1 - Employee Only',
            'option2_employee' => 'Option 2 - Employee Only',
            'option1_spouse' => 'Option 1 - Employee/Spouse',
            'option2_spouse' => 'Option 2 - Employee/Spouse',
            'option1_child' => 'Option 1 - Employee/Child(ren)',
            'option2_child' => 'Option 2 - Employee/Child(ren)',
            'option1_family' => 'Option 1 - Employee/Family',
            'option2_family' => 'Option 2 - Employee/Family',
            'waive' => 'Waive',
            default => '',
        };
    }

    private function formatCoveragePlanLabel(?string $value): string
    {
        return match ($value) {
            'employee' => 'Employee',
            'child' => 'Employee/Child(ren)',
            'spouse' => 'Employee/Spouse',
            'family' => 'Family',
            'waive' => 'Waive',
            default => '',
        };
    }

    private function formatLifeOptionLabel(?string $value): string
    {
        return match ($value) {
            'elect' => 'Elect',
            'waive' => 'Waive',
            default => '',
        };
    }

    private function formatTaxMethodLabel(?string $value): string
    {
        return match ($value) {
            'pre_tax' => 'Pre-Tax',
            'after_tax' => 'After-Tax',
            default => '',
        };
    }

    private function formatEmployeeCompanyNames(Employee $employee): string
    {
        $names = collect()
            ->merge($employee->employeeRates ?? [])
            ->merge($employee->employeeRatesRequests ?? [])
            ->map(fn ($rate) => $rate->company?->name)
            ->filter()
            ->unique()
            ->values();

        return $names->isEmpty() ? 'N/A' : $names->implode(', ');
    }

    private function formatBenefitStatus(Employee $employee): string
    {
        if ($employee->benefitEnrollment?->benefit_acknowledgment === 'decline') {
            return 'Declined';
        }

        if ($employee->benefit_submitted_at) {
            return 'Submitted';
        }

        return 'Not Submitted';
    }

    private function formatExportDate($date): string
    {
        if ($date === null || $date === '') {
            return '';
        }

        return Carbon::parse($date)->format('M j, Y');
    }

    /**
     * Search employees for sending benefit mail
     */
    public function searchEmployees(Request $request)
    {
        $search = $request->get('search', '');

        $query = Employee::query()
            ->with(['employeeRates.company.state', 'employeeRatesRequests.company.state', 'aliases'])
            // ->where('active', 1)
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

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('pos_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('aliases', function ($a) use ($search) {
                        $a->where('alias_name', 'like', "%{$search}%")
                            ->orWhere('alias_employee_id', 'like', "%{$search}%");
                    });
            });
        }

        $employees = $query->limit(20)->get();

        return response()->json([
            'data' => $employees
        ]);
    }

    /**
     * Create a new employee for benefit enrollment
     */
    public function createEmployee(Request $request)
    {
        $this->authorize('access', 'benefits.send-mail');

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|string|max:255',
            'pos_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'benefits_state' => 'required|string|in:' . implode(',', config('benefit_election.supported_states', [])),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $workgroupId = session('workgroup');

        if (! $workgroupId) {
            return response()->json([
                'success' => false,
                'message' => 'Workgroup is required',
            ], 422);
        }

        $employeeId = trim($request->employee_id);
        $existing = Employee::where('workgroup_id', $workgroupId)
            ->where(function ($q) use ($employeeId) {
                $q->where('employee_id', $employeeId)
                    ->orWhereHas('aliases', fn ($a) => $a->where('alias_employee_id', $employeeId));
            })
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Employee with this ID already exists',
            ], 422);
        }

        $employee = Employee::create([
            'employee_id' => $employeeId,
            'pos_name' => trim($request->pos_name),
            'email' => trim($request->email),
            'benefits_state' => BenefitElection::resolveStateCode($request->benefits_state),
            'workgroup_id' => $workgroupId,
            'active' => true,
            'employee_type' => 'Completed',
            'i9_doc_skip' => true,
            'move_from_pending' => true,
            'hire_date' => now()->toDateString(),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        $employee->load(['employeeRates.company.state', 'employeeRatesRequests.company.state']);

        return response()->json([
            'success' => true,
            'message' => 'Employee created successfully',
            'data' => $employee,
        ]);
    }

    /**
     * Send benefit mail to employee
     */
    public function sendBenefitMail(Request $request)
    {
        $this->authorize('access','benefits.send-mail');
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employee,id',
            'email' => 'required|email',
            'benefits_state' => 'required|string|in:' . implode(',', config('benefit_election.supported_states', [])),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $employee = Employee::with(['employeeRates', 'employeeRatesRequests'])->findOrFail($request->employee_id);
            $employee->benefits_state = $request->benefits_state;

            // Send benefit mail
            $mailService = new MailService();
            $subject = 'Action Required: Health Insurance Enrollment';
            $benefitCode = generateUniqueRandomNumber('employee', 'benefit_code');
            $body = $this->generateBenefitMailContent($employee, $benefitCode);
            
            $response = $mailService->sendMail(
                $request->email,
                $subject,
                $body
            );

            if ($response['messageId'] ?? false) {
                // Update employee record

                $employee->benefit_code = $benefitCode;
                $employee->benefit_mail_sent = now();
                $employee->benefit_submitted_at = null;
                $employee->email = $request->email;
                if ($employee->isDirty()) {
                    $onboarding = OnboardingList::where('employee_id', $employee->id)->first();
                    if ($onboarding && $onboarding->applicant_email != $request->email) {
                        $onboarding->applicant_email = $request->email;
                        $onboarding->save();
                    }
                    $employee->save();
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Benefit mail sent successfully'
                ]);
            } else {
                info($response);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send email'
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate benefit mail content
     */
    private function generateBenefitMailContent($employee, $benefitCode)
    {
        try{

            $name = $employee->pos_name ?? ($employee->aliasNames()[0] ?? 'Employee');
            $employeeId = $employee->employee_id ?? ($employee->aliasIds()[0] ?? 'N/A');
            $companyName = $employee->company->name ?? 'Company';
            $link = url('benefit-enrollment?employee_id=' . encrypt($employee->id) . '&benefit_code=');
            
            return view('emails.benefit-enrollment', [
                'name' => $name,
                'employeeId' => $employeeId,
                'companyName' => $companyName,
                'link' => $link,
                'benefitCode' => $benefitCode,
                ])->render();
        } catch (\Exception $e) {
            info($e);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkBenefitSubmitted(Request $request)
    {
        $employee = Employee::findOrFail(decrypt($request->employee_id));
        return response()->json([
            'submitted' => $employee->benefit_submitted_at ? true : false,
        ]);
    }

    public function verifyBenefitCode(Request $request)
    {
        $employee = Employee::findOrFail(decrypt($request->employee_id));
        if ($employee->benefit_code == $request->benefit_code) {
            return response()->json([
                'valid' => true,
            ]);
        }
        return response()->json([
            'valid' => $employee->benefit_code == $request->benefit_code ? true : false,
        ]);
    }
}
