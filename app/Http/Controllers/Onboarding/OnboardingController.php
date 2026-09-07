<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Onboarding\BenefitEnrollment;
use App\Models\Onboarding\FormI9;
use App\Models\Onboarding\FormW4;
use App\Models\Onboarding\I9W4;
use App\Models\Onboarding\I9PreparerTranslator;
use App\Models\Onboarding\I9Reverification;
use App\Models\Onboarding\OnboardingHandbook;
use App\Models\Onboarding\OnboardingList;
use App\Models\Onboarding\DirectDeposit;
use App\Models\Onboarding\EmergencyContact;
use App\Models\Onboarding\FinalSubmission;
use App\Models\Onboarding\DigitalSignature;
use App\Models\Onboarding\EmployeeDocument;
use App\Models\Settings\Company;
use App\Models\User;
use App\Services\FileUploadService;
use App\Services\I9W4SubmittedNotificationService;
use App\Services\MailService;
use App\Support\BenefitElection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Barryvdh\DomPDF\Facade\Pdf;


use App\Services\WorkbrightService;
use Illuminate\Support\Str;

class OnboardingController extends Controller
{
    protected $workbrightService;

    public function __construct(WorkbrightService $workbrightService)
    {
        $this->workbrightService = $workbrightService;
    }

    private function extractWorkBrightEmployeeId(array $data): ?string
    {
        $id = data_get($data, 'id')
            ?? data_get($data, 'employee_id')
            ?? data_get($data, 'employee.id')
            ?? data_get($data, 'data.id')
            ?? data_get($data, 'data.employee_id')
            ?? data_get($data, 'data.employee.id');

        return $id !== null && $id !== '' ? (string) $id : null;
    }


    private function createWorkBrightEmployee(OnboardingList $onboarding): array
    {
        $apiKey = $this->workbrightService->workBrightApiKey();
        if ($apiKey === '') {
            throw new \RuntimeException('WORK_BRIGHT_API_KEY is missing.');
        }

        $startDate = $onboarding->doj ?? optional($onboarding->employee)->doj;

        $workgroup = $onboarding->employee?->workgroup;
        try {
            $startDate = $startDate
                ? Carbon::parse($startDate)->toDateString()
                : now()->toDateString();
        } catch (\Throwable $e) {
            $startDate = now()->toDateString();
        }

        $groups = $this->workbrightService->workBrightGroups();
        $groupId = null;
        foreach ($groups as $group) {
            if ($group['name'] === $workgroup?->name) {
                $groupId = $group['id'];
                break;
            }
        }


        $payload = [
            'employee' => [
                'first_name' => $onboarding->applicant_first_name,
                'last_name' => $onboarding->applicant_last_name,
                'email' => $onboarding->applicant_email,
                'external_id' => (string) $onboarding->onboarding_number,
                "send_email" => true,
                'employment' => [
                    'start_date' => $startDate,
                    'hire_date' => $startDate,
                    "onboarding_start_date" => $startDate,
                ],
                'groups' => [
                    $groupId,
                ]
            ]
        ];


        $packetId = $this->workbrightService->workBrightPacketId();
        if (!empty($packetId)) {
            $payload['employee']['packet_id'] = $packetId;
        }

        /** @var \Illuminate\Http\Client\Response $response */
        $response = $this->workbrightService->workBrightHttpClient()
            ->asJson()
            ->post($this->workbrightService->workBrightBaseUrl() . '/api/employees', $payload);

        if (!$response->successful()) {
            if (Str::contains($response->body(), 'This email already exists')) {
                return [
                    'message' => 'This email already exists',
                    'error' => 'This email already exists',
                ];
            }
        }

        $responseData = (array) $response->json();
        $employeeId = $this->extractWorkBrightEmployeeId($responseData);
        if (!$employeeId) {
            throw new \RuntimeException('WorkBright employee id not found in create response.');
        }

        return [
            'employee_id' => $employeeId,
            'response' => $responseData,
        ];
    }


    public function index(Request $request)
    {
        $this->authorize('access', 'onboarding.index');

        $onboardings = OnboardingList::with('employee')->filter();
        return to_json([
            'collection' => $onboardings,
        ]);
    }
    public function handleSendMailToEmployee($employeeId)
    {
        try {

            $onboardingNumber = generateUniqueRandomNumber('onboarding_list', 'onboarding_number', $length = 10);

            $employee = Employee::with(['employeeRates.company', 'employeeRatesRequests.company'])->find($employeeId);
            // if (!$employee || $employee->onboarding_status != 'pending') {
            //     return to_json([
            //         'message' => 'Employee not found or already onboarded',
            //         'error' => 'Employee not found or already onboarded',
            //     ], 404);
            // }
            if (!$employee->email) {
                return to_json([
                    'message' => 'Employee email not found',
                    'error' => 'Employee email not found',
                ], 404);
            }

            $posname = explode(' ', $employee->pos_name);
            $address = $employee->street . ', ' . $employee->city . ', ' . $employee->state . ' ' . $employee->zip;
            $employeeRate = $employee->employeeRates->first();
            $employeeRateRequest = $employee->employeeRatesRequests->first();
            $company = $employeeRate?->company ?? $employeeRateRequest?->company;

            if (!$company && !$employee->benefits_state) {
                return to_json([
                    'message' => 'Benefits state is required when employee has no company',
                    'error' => 'Benefits state is required when employee has no company',
                ], 422);
            }
            $onboarding = OnboardingList::where('employee_id', $employee->id)->first();
            if (!$onboarding) {
                $onboarding = new OnboardingList();
                $onboarding->employee_id = $employee->id;
                $onboarding->pos_id = $employee->employee_id;
                $onboarding->process_id = 1;
                $onboarding->onboarding_number = $onboardingNumber;
                $onboarding->employee_handbook_agreed = false;
                $onboarding->application_job_id = null;
                $onboarding->job_id = null;
                $onboarding->company_id = $company?->id;
                $onboarding->store_user_id = null;
                $onboarding->user_id = null;
                $onboarding->nick_name = $employee->pos_name;
                $onboarding->real_name = $employee->pos_name;
                $onboarding->applicant_first_name = $employee->first_name;
                $onboarding->applicant_middle_initial = $employee->middle_name;
                $onboarding->applicant_last_name = $employee->last_name;
                $onboarding->applicant_address = $address;
                $onboarding->applicant_contact_number = $employee->phone;
                $onboarding->applicant_email = $employee->email;
                $onboarding->city = $employee->city;
                $onboarding->state = $employee->state;
                $onboarding->zip_code = $employee->zip;
                $onboarding->zipcode = $employee->zipcode;
                $onboarding->applicant_status = null;
                $onboarding->applicant_hire_detail = null;
                $onboarding->sent_email_hire_data = null;
                $onboarding->applicant_pay_type = null;
                $onboarding->applicant_pay_period = null;
                $onboarding->status = 'pending';
                $onboarding->final_status = 'pending';
                $onboarding->created_by = Auth::user()->id;
                $onboarding->verified_i9 = null;
                $onboarding->verified_w4 = null;
                $onboarding->read_date = null;
                $onboarding->verified_date = null;
                $onboarding->submit_application_date = null;
                $onboarding->remark = null;
                $onboarding->alternative_procedure = null;
                $onboarding->doj = $employeeRateRequest?->effective_date ?? null;
                $onboarding->benifits = null;
                $onboarding->date = now();
                $onboarding->new_change = null;
                $onboarding->final_onboarding_hr_status = null;
                $onboarding->hr_verify_date = null;
                $onboarding->send_onboarding_email_date = null;
                $onboarding->new_process_status = null;
                $onboarding->picture_file = null;
                $onboarding->emergency_contact_name = $employee->emergency_contact_name;
                $onboarding->emergency_contact_phone = $employee->emergency_contact_phone;
                $onboarding->emergency_contact_relationship = $employee->emergency_contact_relationship;
                $onboarding->dob = $employee->dob;
                $onboarding->i9_with_work_bright = $this->workbrightService->isI9WithWorkBrightEnabled();
                $onboarding->save();
            } else {
                $onboarding->process_id = 1;
                $onboarding->status = 'pending';
                $onboarding->final_status = 'pending';
                $onboarding->save();
            }


            $employeeEmail = $employee->email ?? 'mpatel@tdpus.com';
            $processId = custom_encrypt(1);

            $onboarding_link = url('onboarding-process?onboardingId=' . encrypt($onboarding->onboarding_number) . '&userId=' . encrypt($employeeEmail) . '&processId=' . $processId);

            $mailData = [
                'userName'       => $employee->pos_name,
                'storeName'      => $company?->name ?? "Papa John's",
                'onboardingLink' => $onboarding_link,
                'onboardingCode' => $onboarding->onboarding_number
            ];
            $htmlContent = view('emails.employee-mail', $mailData)->render();
            if (!config('app.skip_onboarding')) {
                $success = MailService::sendMail(
                    $employeeEmail,
                    'Welcome Aboard! Onboarding Process Details Inside.',
                    $htmlContent
                );
            }

            $employee->update(['mail_sent' => true]);
            return to_json([
                'message' => 'Onboarding email sent to employee successfully',
                'id' => $onboarding->id,
            ], 200);
        } catch (\Exception $e) {
            dd($e);
            return to_json([
                'saved' => false,
                'message' => 'Failed to send onboarding email to employee',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function sendMailToEmp(Request $request)
    {
        $this->authorize('access', 'employee.create');
        $request->validate([
            'employee_id' => 'required|integer',
            'email' => 'nullable|email',
            'benefits_state' => 'nullable|string|in:' . implode(',', config('benefit_election.supported_states', [])),
        ]);

        $employee = Employee::with(['employeeRates.company', 'employeeRatesRequests.company'])
            ->find($request->employee_id);

        if (!$employee) {
            return to_json([
                'message' => 'Employee not found',
                'error' => 'Employee not found',
            ], 404);
        }

        $hasCompany = $employee->employeeRates->contains(fn($rate) => (bool) $rate->company_id)
            || $employee->employeeRatesRequests->contains(fn($rate) => (bool) $rate->company_id);

        if (!$hasCompany) {
            $request->validate([
                'benefits_state' => 'required|string|in:' . implode(',', config('benefit_election.supported_states', [])),
            ]);
            $employee->benefits_state = $request->benefits_state;
        }

        if ($request->filled('email')) {
            $employee->email = $request->email;

            $onboarding = OnboardingList::where('employee_id', $employee->id)->first();
            if ($onboarding && $onboarding->applicant_email != $request->email) {
                $onboarding->applicant_email = $request->email;
                $onboarding->save();
            }
        }

        if ($employee->isDirty()) {
            $employee->save();
        }

        return $this->handleSendMailToEmployee($request->employee_id);
    }

    /**
     * Verify onboarding code and set cookie so the employee can access the process.
     * Public route (no auth) for the onboarding-process page.
     */
    public function verifyOnboardingCode(Request $request)
    {
        $request->validate([
            'onboardingId' => 'required|string',
            'onboardingCode' => 'required|string',
        ]);

        try {
            $onboardingNumber = Crypt::decrypt($request->onboardingId);
        } catch (\Exception $e) {
            return to_json([
                'message' => 'Invalid onboarding link.',
                'error' => 'Invalid onboarding link.',
            ], 400);
        }

        $code = trim($request->onboardingCode);
        if ($code !== $onboardingNumber) {
            return to_json([
                'message' => 'The onboarding code you entered is incorrect.',
                'error' => 'Invalid code.',
            ], 422);
        }

        $exists = OnboardingList::where('onboarding_number', $onboardingNumber)->exists();
        if (!$exists) {
            return to_json([
                'message' => 'Onboarding record not found.',
                'error' => 'Not found.',
            ], 404);
        }
        $cookie = Cookie::make('onboardingId', $request->onboardingId, 60 * 24 * 7, '/', null, false, false);
        return response()->json(['message' => 'Code verified successfully.'])
            ->cookie($cookie);
    }

    /**
     * Return minimal onboarding + company data for the public onboarding process (step 1 welcome, etc.).
     * Expects encrypted onboardingId (from the onboarding link). Optionally validate via cookie.
     */
    public function getOnboardingData(Request $request)
    {
        $request->validate([
            'onboardingId' => 'required|string',
        ]);

        try {
            $onboardingNumber = Crypt::decrypt($request->onboardingId);
        } catch (\Exception $e) {
            return to_json([
                'message' => 'Invalid onboarding link.',
                'error' => 'Invalid onboarding link.',
            ], 400);
        }

        $onboarding = OnboardingList::with(['company', 'employee.workgroup'])->whereDoesntHave('employee', function ($query) {
            $query->where('rejected', true);
        })->where('onboarding_number', $onboardingNumber)->first();
        if (!$onboarding || !$onboarding->employee) {
            return to_json([
                'message' => 'Onboarding record not found.',
                'error' => 'Not found.',
            ], 404);
        }
        return to_json(array_merge(
            $onboarding->toArray()
        ));
    }


    public function getHandbookData(Request $request)
    {
        $request->validate([
            'onboardingId' => 'required|string',
        ]);

        try {
            $onboardingNumber = Crypt::decrypt($request->onboardingId);
        } catch (\Exception $e) {
            return to_json([
                'message' => 'Invalid onboarding link.',
                'error' => 'Invalid onboarding link.',
            ], 400);
        }
        $onboarding = OnboardingList::where('onboarding_number', $onboardingNumber)->first();
        if (!$onboarding) {
            return to_json([
                'message' => 'Onboarding record not found.',
                'error' => 'Not found.',
            ], 404);
        }
        return to_json([
            'success' => true,
            'data' => [
                'employee_sign' => $onboarding->handbook?->employee_sign ?? '',
                'date' => $onboarding->handbook?->date ?? '',
                'print_full_name' => $onboarding->handbook?->print_full_name ?? '',
                'is_checked' => (bool) $onboarding->handbook?->is_checked ?? false,
            ],
        ]);
    }
    /**
     * Store employee handbook data (signature, date, print name) and mark handbook agreed.
     */
    public function saveEmployeeHandbook(Request $request)
    {
        $request->validate([
            'onboardingId' => 'required|string',
            'employee_sign' => 'required|string|max:255',
            'date' => 'required|string|max:255',
            'print_full_name' => 'required|string|max:255',
            'is_checked' => 'required|boolean',
        ]);

        try {
            $onboardingNumber = Crypt::decrypt($request->onboardingId);
        } catch (\Exception $e) {
            return to_json([
                'message' => 'Invalid onboarding link.',
                'error' => 'Invalid onboarding link.',
            ], 400);
        }

        $onboarding = OnboardingList::where('onboarding_number', $onboardingNumber)->first();
        if (!$onboarding) {
            return to_json([
                'message' => 'Onboarding record not found.',
                'error' => 'Not found.',
            ], 404);
        }

        OnboardingHandbook::updateOrCreate(
            ['onboarding_id' => $onboarding->id],
            [
                'employee_sign' => $request->employee_sign,
                'date' => $request->date,
                'print_full_name' => $request->print_full_name,
                'is_checked' => $request->is_checked,
            ]
        );

        $onboarding->employee_handbook_agreed = true;
        $onboarding->process_id = $onboarding->process_id == 1 ? 2 : $onboarding->process_id;
        $onboarding->save();

        Employee::where('id', $onboarding->employee_id)->update(['check_name' => $request->print_full_name]);

        return to_json([
            'message' => 'Employee handbook saved successfully.',
        ], 200);
    }

    /**
     * Get profile data for Step 3 (Create Your Profile). Returns employee + work permit from onboarding.
     */
    public function getProfileData(Request $request)
    {


        $request->validate([
            'onboardingId' => 'required|string',
        ]);

        try {
            $onboardingNumber = Crypt::decrypt($request->onboardingId);

            $onboarding = OnboardingList::with('employee')->where('onboarding_number', $onboardingNumber)->first();
            if (!$onboarding || !$onboarding->employee) {
                return to_json([
                    'message' => 'Onboarding or employee record not found.',
                    'error' => 'Not found.',
                ], 404);
            }

            $fileUploadService = app(FileUploadService::class);
            $workPermitUrl = $onboarding->work_permit_document_path
                ? $fileUploadService->url($onboarding->work_permit_document_path)
                : '';

            return to_json([
                'success' => true,
                'data' => [
                    'first_name' => $onboarding->applicant_first_name ?? '',
                    'last_name' => $onboarding->applicant_last_name ?? '',
                    'middle_initial' => $onboarding->applicant_middle_initial ?? '',
                    'pos_name' => $onboarding->nick_name ?? '',
                    'pos_id' => $onboarding->employee?->employee_id ?? '',
                    'address_street' => $onboarding->applicant_address ?? '',
                    'apt_number' => $onboarding->apt_number ?? '',
                    'city' => $onboarding->city ?? '',
                    'state' => $onboarding->state ?? '',
                    'zip_code' => $onboarding->zip_code ?? '',
                    'dob' => $onboarding->dob ? (\Carbon\Carbon::parse($onboarding->dob)->format('Y-m-d')) : '',
                    'work_permit_issuer' => $onboarding->work_permit_issuer ?? '',
                    'work_permit_document_url' => $workPermitUrl,
                    'email' => $onboarding->applicant_email ?? '',
                    'phone' => $onboarding->applicant_contact_number ?? '',
                    'emergency_contact_name' => $onboarding->emergency_contact_name ?? '',
                    'emergency_contact_phone' => $onboarding->emergency_contact_phone ?? '',
                    'emergency_contact_relationship' => $onboarding->emergency_contact_relationship ?? '',
                ],
            ]);
        } catch (\Exception $e) {
            return to_json([
                'message' => 'Invalid onboarding link.',
                'error' => 'Invalid onboarding link.',
            ], 400);
        }
    }

    /**
     * Save Create Your Profile (Step 3) and advance process_id.
     * Accepts multipart form data with optional work_permit_file in the same payload.
     */
    public function saveProfile(Request $request, FileUploadService $fileUploadService)
    {


        $request->validate([
            'onboardingId' => 'required|string',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'emergency_contact_name' => 'required|string|max:255',
            'emergency_contact_phone' => 'required|string|max:255',
            'emergency_contact_relationship' => 'required|string|max:255',
            'work_permit_file' => 'nullable|file|' . upload_max_file_size_rule(),
            'dob' => 'required|string|max:255'
        ]);

        try {
            $onboardingNumber = Crypt::decrypt($request->onboardingId);
        } catch (\Exception $e) {
            return to_json([
                'message' => 'Invalid onboarding link.',
                'error' => 'Invalid onboarding link.',
            ], 400);
        }

        $onboarding = OnboardingList::with('employee')->where('onboarding_number', $onboardingNumber)->first();
        if (!$onboarding) {
            return to_json([
                'message' => 'Onboarding or employee record not found.',
                'error' => 'Not found.',
            ], 404);
        }

        if ($request->hasFile('work_permit_file')) {
            try {

                if ($onboarding->work_permit_document_path) {
                    $fileUploadService->delete($onboarding->work_permit_document_path);
                }

                $result = $fileUploadService->store(
                    $request->file('work_permit_file'),
                    'employee-documents/' . $onboarding->employee_id . '/work-permits',
                    'public'
                );
                $onboarding->work_permit_document_path = $result['path'];


                uploadDocument('work_permit', $result['path'], $onboarding->employee_id);
            } catch (\Exception $e) {
                return to_json([
                    'message' => 'Failed to upload work permit file. Please try again.',
                    'error' => 'File upload failed: ' . $e->getMessage(),
                ], 500);
            }
        }
        $onboarding->work_permit_issuer = $request->input('work_permit_issuer') ?? $onboarding->work_permit_issuer;

        $onboarding->applicant_first_name = $request->first_name;
        $onboarding->applicant_last_name = $request->last_name;
        $onboarding->applicant_middle_initial = $request->middle_initial ?? null;
        $onboarding->applicant_address = $request->address_street ?? null;
        $onboarding->apt_number = $request->apt_number ?? null;
        $onboarding->city = $request->city ?? null;
        $onboarding->state = $request->state ?? null;
        $onboarding->zip_code = $request->zip_code ?? null;
        $onboarding->dob = $request->dob ?: null;
        $onboarding->applicant_email = $request->email;
        $onboarding->applicant_contact_number = $request->phone ?? null;
        $onboarding->emergency_contact_name = $request->emergency_contact_name ?? null;
        $onboarding->emergency_contact_phone = $request->emergency_contact_phone ?? null;
        $onboarding->emergency_contact_relationship = $request->emergency_contact_relationship ?? null;
        if ($onboarding->process_id == 2) {
            // Skip I-9 and W-4 steps when WorkBright flow is enabled.
            // $onboarding->process_id = $onboarding->i9_with_work_bright ? 5 : 3;
            $onboarding->process_id = $this->workbrightService->isI9WithWorkBrightEnabled() ? 5 : 3;
        }
        $onboarding->save();

        $employee = Employee::find($onboarding->employee_id);
        if ($employee) {
            $employee->pos_name = $request->pos_name ?? null;
            $employee->employee_id = $request->pos_id ?? null;
            $employee->street = $request->address_street ?? null;
            $employee->city = $request->city ?? null;
            $employee->state = $request->state ?? null;
            $employee->zip = $request->zip_code ?? null;
            $employee->dob = $request->dob ?: null;
            $employee->emergency_contact_name = $request->emergency_contact_name ?? null;
            $employee->emergency_contact_phone = $request->emergency_contact_phone ?? null;
            $employee->emergency_contact_relationship = $request->emergency_contact_relationship ?? null;
            $employee->email = $request->email ?? null;
            $employee->phone = $request->phone ?? null;
            $employee->onboarding_status = 'in_complete_form';
            $employee->save();
        }


        return to_json([
            'message' => 'Profile saved successfully.',
        ], 200);
    }

    /**
     * Upload work permit document for onboarding (optional separate endpoint).
     * Prefer sending the file with save-profile in one payload.
     */
    public function uploadWorkPermit(Request $request, FileUploadService $fileUploadService)
    {
        $request->validate([
            'onboardingId' => 'required|string',
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|' . upload_max_file_size_rule(),
        ]);

        try {
            $onboardingNumber = Crypt::decrypt($request->onboardingId);
        } catch (\Exception $e) {
            return to_json([
                'message' => 'Invalid onboarding link.',
                'error' => 'Invalid onboarding link.',
            ], 400);
        }

        $onboarding = OnboardingList::where('onboarding_number', $onboardingNumber)->first();
        if (!$onboarding) {
            return to_json([
                'message' => 'Onboarding record not found.',
                'error' => 'Not found.',
            ], 404);
        }

        try {
            $result = $fileUploadService->store(
                $request->file('file'),
                'employee-documents/' . $onboarding->employee_id . '/work-permits',
                'public'
            );
            $onboarding->work_permit_document_path = $result['path'];
            $onboarding->save();

            uploadDocument('work_permit', $result['path'], $onboarding->employee_id);

            return to_json([
                'success' => true,
                'url' => $result['url'],
            ], 200);
        } catch (\Exception $e) {
            return to_json([
                'message' => 'Failed to upload work permit file. Please try again.',
                'error' => 'File upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get I-9 form data for Step 4. Returns Section 1 from onboarding + existing I-9 form if any.
     */
    public function getI9Data(Request $request)
    {
        $request->validate([
            'onboardingId' => 'required|string',
        ]);

        try {
            $onboardingNumber = Crypt::decrypt($request->onboardingId);
        } catch (\Exception $e) {
            return to_json([
                'message' => 'Invalid onboarding link.',
                'error' => 'Invalid onboarding link.',
            ], 400);
        }

        $onboarding = OnboardingList::with(['employee.workgroup', 'formI9.preparerTranslators', 'formI9.reverifications'])
            ->where('onboarding_number', $onboardingNumber)->first();

        if (!$onboarding) {
            return to_json([
                'message' => 'Onboarding record not found.',
                'error' => 'Not found.',
            ], 404);
        }

        $section1 = [
            'last_name' => $onboarding->applicant_last_name ?? '',
            'first_name' => $onboarding->applicant_first_name  ?? '',
            'middle_initial' => $onboarding->applicant_middle_initial ?? '',
            'address' => $onboarding->applicant_address ?? $onboarding->employee?->street ?? '',
            'apt_number' => $onboarding->apt_number ?? $onboarding->employee?->apt_number ?? '',
            'city' => $onboarding->city ?? $onboarding->employee?->city ?? '',
            'state' => $onboarding->state ?? $onboarding->employee?->state ?? '',
            'zipcode' => $onboarding->zip_code ?? $onboarding->zipcode ?? $onboarding->employee?->zip ?? '',
            'date_of_birth' => $onboarding->dob ? $onboarding->dob : ($onboarding->employee?->dob ?? ''),
            'social_security_number' => $onboarding->employee?->ssn ?? '',
            'employee_email' => $onboarding->applicant_email ?? $onboarding->employee?->email ?? '',
            'employee_telephone' => $onboarding->applicant_contact_number ?? $onboarding->employee?->phone ?? '',
        ];

        $i9 = $onboarding->formI9;
        $fileUploadService = app(FileUploadService::class);
        if ($i9) {
            $i9->list_a_file_path = isset($i9->list_a_file_path) ? $fileUploadService->url($i9->list_a_file_path) : null;
            $i9->list_b_file_path = isset($i9->list_b_file_path) ? $fileUploadService->url($i9->list_b_file_path) : null;
            $i9->list_c_file_path = isset($i9->list_c_file_path) ? $fileUploadService->url($i9->list_c_file_path) : null;
            $i9->additional_document_path = isset($i9->additional_document_path) ? $fileUploadService->url($i9->additional_document_path) : null;
        }
        $company = $onboarding->company;
        $form = null;
        if ($i9) {
            $form = array_merge($i9->only([
                'other_last_names',
                'citizenship_status',
                'uscis_or_a_number',
                'alien_authorized_exp_date',
                'uscis_a_number',
                'form_i94_admission_number',
                'foreign_passport_number',
                'employee_signature',
                'section1_today_date',
                'list_a_doc_title_1',
                'list_a_issuing_authority_1',
                'list_a_document_number_1',
                'list_a_expiration_date_1',
                'list_a_doc_title_2',
                'list_a_issuing_authority_2',
                'list_a_document_number_2',
                'list_a_expiration_date_2',
                'list_a_doc_title_3',
                'list_a_issuing_authority_3',
                'list_a_document_number_3',
                'list_a_expiration_date_3',
                'list_b_doc_title',
                'list_b_issuing_authority',
                'list_b_document_number',
                'list_b_expiration_date',
                'list_c_doc_title',
                'list_c_issuing_authority',
                'list_c_document_number',
                'list_c_expiration_date',
                'additional_information',
                'alternative_procedure',
                'first_day_employment',
                'employer_name',
                'employer_signature',
                'employer_today_date',
                'employer_business_name',
                'employer_business_address',
                'list_a_file_path',
                'list_b_file_path',
                'list_c_file_path',
                'additional_document_path',
                'additional_document_label',
            ]), [
                'preparer_translators' => $i9->preparerTranslators->map(fn($p) => $p->only([
                    'signature',
                    'signature_date',
                    'last_name',
                    'first_name',
                    'middle_initial',
                    'address',
                    'city',
                    'state',
                    'zip_code',
                ]))->toArray(),
                'reverifications' => $i9->reverifications->map(fn($r) => $r->only([
                    'rehire_date',
                    'new_last_name',
                    'new_first_name',
                    'new_middle_initial',
                    'document_title',
                    'document_number',
                    'expiration_date',
                    'employer_representative_name',
                    'employer_signature',
                    'today_date',
                    'additional_information',
                    'alternative_procedure_dhs',
                ]))->toArray(),
            ]);
        } else {
            $form = [
                'employer_name' => $company->name,
                'employer_signature' => $company->name,
                'employer_business_name' => $company->name,
                'employer_business_address' => $company->address,
                'first_day_employment' => $onboarding->doj ? $onboarding->doj : null,
                'employer_today_date' => now()->format('Y-m-d'),
            ];
        }

        return to_json([
            'success' => true,
            'section1' => $section1,
            'form' => $form,
            'employee' => $onboarding->employee ? [
                'id' => $onboarding->employee->id,
                'workgroup' => $onboarding->employee->workgroup ? [
                    'id' => $onboarding->employee->workgroup->id,
                    'name' => $onboarding->employee->workgroup->name,
                ] : null,
            ] : null,
        ]);
    }

    /**
     * Save I-9 form (Step 4). Creates or updates i9_forms and related preparer/reverification rows.
     */
    public function saveI9(Request $request)
    {
        $request->validate([
            'onboardingId' => 'required|string',
            'list_a_file' => 'nullable|file|' . upload_max_file_size_rule(),
            'list_b_file' => 'nullable|file|' . upload_max_file_size_rule(),
            'list_c_file' => 'nullable|file|' . upload_max_file_size_rule(),
            'additional_document_file' => 'nullable|file|' . upload_max_file_size_rule(),
        ]);

        try {
            $onboardingNumber = Crypt::decrypt($request->onboardingId);
        } catch (\Exception $e) {
            return to_json([
                'message' => 'Invalid onboarding link.',
                'error' => 'Invalid onboarding link.',
            ], 400);
        }

        $onboarding = OnboardingList::where('onboarding_number', $onboardingNumber)->first();
        if (!$onboarding) {
            return to_json([
                'message' => 'Onboarding record not found.',
                'error' => 'Not found.',
            ], 404);
        }

        // Support FormData: decode JSON fields if present
        $payload = $request->all();
        if ($request->has('section1') && is_string($request->input('section1'))) {
            $decoded = json_decode($request->input('section1'), true);
            if (is_array($decoded)) {
                $payload['section1'] = $decoded;
            }
        }
        if ($request->has('form') && is_string($request->input('form'))) {
            $decoded = json_decode($request->input('form'), true);
            if (is_array($decoded)) {
                $payload = array_merge($payload, $decoded);
            }
        }
        $isResubmitI9 = $onboarding->formI9 !== null;
        $i9 = $onboarding->formI9 ?? new FormI9();
        $i9->employee_id = $onboarding->employee_id;
        $i9->onboarding_list_id = $onboarding->id;
        $i9->other_last_names = $payload['other_last_names'] ?? null;
        $i9->citizenship_status = isset($payload['citizenship_status']) ? (int) $payload['citizenship_status'] : null;
        $i9->uscis_or_a_number = $payload['uscis_or_a_number'] ?? null;
        $i9->alien_authorized_exp_date = $payload['alien_authorized_exp_date'] ?? null;
        $i9->uscis_a_number = $payload['uscis_a_number'] ?? null;
        $i9->form_i94_admission_number = $payload['form_i94_admission_number'] ?? null;
        $i9->foreign_passport_number = $payload['foreign_passport_number'] ?? null;
        $i9->employee_signature = $payload['employee_signature'] ?? null;
        $i9->section1_today_date = $payload['section1_today_date'] ?? null;
        $i9->list_a_doc_title_1 = $payload['list_a_doc_title_1'] ?? null;
        $i9->list_a_issuing_authority_1 = $payload['list_a_issuing_authority_1'] ?? null;
        $i9->list_a_document_number_1 = $payload['list_a_document_number_1'] ?? null;
        $i9->list_a_expiration_date_1 = $payload['list_a_expiration_date_1'] ?? null;
        $i9->list_a_doc_title_2 = $payload['list_a_doc_title_2'] ?? null;
        $i9->list_a_issuing_authority_2 = $payload['list_a_issuing_authority_2'] ?? null;
        $i9->list_a_document_number_2 = $payload['list_a_document_number_2'] ?? null;
        $i9->list_a_expiration_date_2 = $payload['list_a_expiration_date_2'] ?? null;
        $i9->list_a_doc_title_3 = $payload['list_a_doc_title_3'] ?? null;
        $i9->list_a_issuing_authority_3 = $payload['list_a_issuing_authority_3'] ?? null;
        $i9->list_a_document_number_3 = $payload['list_a_document_number_3'] ?? null;
        $i9->list_a_expiration_date_3 = $payload['list_a_expiration_date_3'] ?? null;
        $i9->list_b_doc_title = $payload['list_b_doc_title'] ?? null;
        $i9->list_b_issuing_authority = $payload['list_b_issuing_authority'] ?? null;
        $i9->list_b_document_number = $payload['list_b_document_number'] ?? null;
        $i9->list_b_expiration_date = $payload['list_b_expiration_date'] ?? null;
        $i9->list_c_doc_title = $payload['list_c_doc_title'] ?? null;
        $i9->list_c_issuing_authority = $payload['list_c_issuing_authority'] ?? null;
        $i9->list_c_document_number = $payload['list_c_document_number'] ?? null;
        $i9->list_c_expiration_date = $payload['list_c_expiration_date'] ?? null;
        $i9->additional_information = $payload['additional_information'] ?? null;
        $i9->alternative_procedure = (bool) ($payload['alternative_procedure'] ?? false);
        $i9->first_day_employment = $payload['first_day_employment'] ?? null;
        $i9->employer_name = $payload['employer_name'] ?? null;
        $i9->employer_signature = $payload['employer_signature'] ?? null;
        $i9->employer_today_date = $payload['employer_today_date'] ?? null;
        $i9->employer_business_name = $payload['employer_business_name'] ?? null;
        $i9->employer_business_address = $payload['employer_business_address'] ?? null;

        // Document file uploads (List A, B, C and additional/last document)
        $uploadDir = 'employee-documents/' . $onboarding->employee_id . '-' . $onboarding->employee->pos_name;
        $fileService = app(FileUploadService::class);

        if ($request->hasFile('list_a_file')) {
            $oldPath = $i9->list_a_file_path;
            $result = $fileService->store($request->file('list_a_file'), $uploadDir);
            $i9->list_a_file_path = $result['path'];
            if ($oldPath) {
                $fileService->delete($oldPath);
            }

            uploadDocument('list_a', $result['path'], $onboarding->employee_id, 'list_a_' . $i9->list_a_doc_title_1);
        }
        if ($request->hasFile('list_b_file')) {
            $oldPath = $i9->list_b_file_path;
            $result = $fileService->store($request->file('list_b_file'), $uploadDir);
            $i9->list_b_file_path = $result['path'];
            if ($oldPath) {
                $fileService->delete($oldPath);
            }
            uploadDocument('list_b', $result['path'], $onboarding->employee_id, 'list_b_' . $i9->list_b_doc_title);
        }
        if ($request->hasFile('list_c_file')) {
            $oldPath = $i9->list_c_file_path;
            $result = $fileService->store($request->file('list_c_file'), $uploadDir);
            $i9->list_c_file_path = $result['path'];
            if ($oldPath) {
                $fileService->delete($oldPath);
            }
            uploadDocument('list_c', $result['path'], $onboarding->employee_id, 'list_c_' . $i9->list_c_doc_title);
        }
        if ($request->hasFile('additional_document_file')) {
            $oldPath = $i9->additional_document_path;
            $result = $fileService->store($request->file('additional_document_file'), $uploadDir);
            $i9->additional_document_path = $result['path'];
            if ($oldPath) {
                $fileService->delete($oldPath);
            }
            uploadDocument('additional_document', $result['path'], $onboarding->employee_id, 'additional_document_' . $i9->additional_document_label);
        }
        $i9->additional_document_label = $payload['additional_document_label'] ?? null;

        $i9->save();
        $fileUploadService = app(FileUploadService::class);

        // Update onboarding list (and employee) from Section 1 when data is provided
        $section1 = $payload['section1'] ?? $request->input('section1', []);
        if (!empty($section1) && is_array($section1)) {
            $onboarding->applicant_first_name = $section1['first_name'] ?? $onboarding->applicant_first_name;
            $onboarding->applicant_last_name = $section1['last_name'] ?? $onboarding->applicant_last_name;
            $onboarding->applicant_middle_initial = $section1['middle_initial'] ?? $onboarding->applicant_middle_initial;
            $onboarding->applicant_address = $section1['address'] ?? $onboarding->applicant_address;
            $onboarding->apt_number = $section1['apt_number'] ?? $onboarding->apt_number;
            $onboarding->city = $section1['city'] ?? $onboarding->city;
            $onboarding->state = $section1['state'] ?? $onboarding->state;
            $onboarding->zip_code = $section1['zipcode'] ?? $onboarding->zip_code;
            $onboarding->zipcode = $section1['zipcode'] ?? $onboarding->zipcode;
            $onboarding->applicant_contact_number = $section1['employee_telephone'] ?? $onboarding->applicant_contact_number;
            if (!empty($section1['date_of_birth'])) {
                try {
                    $dob = \Carbon\Carbon::createFromFormat('m/d/Y', $section1['date_of_birth'])->format('Y-m-d');
                    $onboarding->dob = $dob;
                } catch (\Exception $e) {
                    $onboarding->dob = $section1['date_of_birth'];
                }
            }
            $onboarding->save();

            $employee = $onboarding->employee;
            if ($employee) {
                $employee->street = $section1['address'] ?? $employee->street;
                $employee->apt_number = $section1['apt_number'] ?? $employee->apt_number;
                $employee->city = $section1['city'] ?? $employee->city;
                $employee->state = $section1['state'] ?? $employee->state;
                $employee->zip = $section1['zipcode'] ?? $employee->zip;
                $employee->phone = $section1['employee_telephone'] ?? $employee->phone;
                $employee->ssn = $section1['social_security_number'] ?? $employee->ssn;
                if (!empty($section1['date_of_birth'])) {
                    try {
                        $employee->dob = \Carbon\Carbon::createFromFormat('m/d/Y', $section1['date_of_birth'])->format('Y-m-d');
                    } catch (\Exception $e) {
                        $employee->dob = $section1['date_of_birth'];
                    }
                }
                $employee->save();
            }
        }

        $i9->preparerTranslators()->delete();
        foreach ($payload['preparer_translators'] ?? $request->input('preparer_translators', []) as $row) {
            if (empty(array_filter($row))) {
                continue;
            }
            I9PreparerTranslator::create([
                'i9_form_id' => $i9->id,
                'signature' => $row['signature'] ?? null,
                'signature_date' => $row['signature_date'] ?? null,
                'last_name' => $row['last_name'] ?? null,
                'first_name' => $row['first_name'] ?? null,
                'middle_initial' => $row['middle_initial'] ?? null,
                'address' => $row['address'] ?? null,
                'city' => $row['city'] ?? null,
                'state' => $row['state'] ?? null,
                'zip_code' => $row['zip_code'] ?? null,
            ]);
        }

        $i9->reverifications()->delete();
        foreach ($payload['reverifications'] ?? $request->input('reverifications', []) as $row) {
            if (empty(array_filter($row))) {
                continue;
            }
            I9Reverification::create([
                'i9_form_id' => $i9->id,
                'rehire_date' => $row['rehire_date'] ?? null,
                'new_last_name' => $row['new_last_name'] ?? null,
                'new_first_name' => $row['new_first_name'] ?? null,
                'new_middle_initial' => $row['new_middle_initial'] ?? null,
                'document_title' => $row['document_title'] ?? null,
                'document_number' => $row['document_number'] ?? null,
                'expiration_date' => $row['expiration_date'] ?? null,
                'employer_representative_name' => $row['employer_representative_name'] ?? null,
                'employer_signature' => $row['employer_signature'] ?? null,
                'today_date' => $row['today_date'] ?? null,
                'additional_information' => $row['additional_information'] ?? null,
                'alternative_procedure_dhs' => (bool) ($row['alternative_procedure_dhs'] ?? false),
            ]);
        }

        if ($onboarding->process_id == 3) {
            $onboarding->process_id = 4;
            $onboarding->save();
        }

        I9W4SubmittedNotificationService::notifyInternalI9($onboarding->fresh(['employee']), $isResubmitI9);

        return to_json([
            'message' => 'Form I-9 saved successfully.',
        ], 200);
    }

    /**
     * Get W-4 form data for Step 5. Returns profile data from onboarding_list + I-9 SSN + existing W4 form.
     */
    public function getW4Data(Request $request)
    {
        $request->validate([
            'onboardingId' => 'required|string',
        ]);
        try {
            $onboardingNumber = Crypt::decrypt($request->onboardingId);
        } catch (\Exception $e) {
            return to_json([
                'message' => 'Invalid onboarding link.',
                'error' => 'Invalid onboarding link.',
            ], 400);
        }

        $onboarding = OnboardingList::with(['company', 'formI9', 'formW4'])
            ->where('onboarding_number', $onboardingNumber)->first();

        if (!$onboarding) {
            return to_json([
                'message' => 'Onboarding record not found.',
                'error' => 'Not found.',
            ], 404);
        }

        // Profile data from onboarding list (used to prefill W-4 Step 1)
        $profile = [
            'first_name' => $onboarding->applicant_first_name ?? '',
            'last_name' => $onboarding->applicant_last_name ?? '',
            'middle_initial' => $onboarding->applicant_middle_initial ?? '',
            'address' => $onboarding->applicant_address ?? '',
            'city' => $onboarding->city ?? '',
            'state' => $onboarding->state ?? '',
            'zip_code' => $onboarding->zip_code ?? '',
        ];
        $cityStateZip = trim(implode(', ', array_filter([
            $profile['city'],
            $profile['state'],
            $profile['zip_code'],
        ])), ', ');

        // SSN from I-9 Section 1 / employee (I-9 form doesn't store SSN; employee may have it)
        $socialSecurityNumber = $onboarding->employee?->ssn ?? '';

        // Name, address, SSN: always from onboarding list (and employee for SSN) — no duplication in w4_forms
        $form = [
            'first_name' => $profile['first_name'],
            'last_name' => $profile['last_name'],
            'middle_initial' => $profile['middle_initial'],
            'social_security_number' => $socialSecurityNumber,
            'address' => $profile['address'],
            'city' => $profile['city'],
            'state' => $profile['state'],
            'zip_code' => $profile['zip_code'],
            'city_state_zip' => $cityStateZip,
            'single_or_married' => null,
            'married_filing' => null,
            'head_of_household' => null,
            'multiple_jobs_and_spouse_works' => null,
            'qualifying_children' => '',
            'dependents' => '',
            'total_amount' => '',
            'other_income' => '',
            'deductions' => '',
            'extra_withholding' => '',
            'employee_sign' => trim($profile['first_name'] . ' ' . $profile['last_name']),
            'employee_date' => '',
            'employer_name' => $onboarding->company?->name ?? '',
            'date_of_employment' => $onboarding->doj ? \Carbon\Carbon::parse($onboarding->doj)->format('m-d-Y') : '',
            'worksheet_line1' => '',
            'worksheet_line2a' => '',
            'worksheet_line2b' => '',
            'worksheet_line2c' => '',
            'worksheet_line3' => '',
            'worksheet_line4' => '',
            'worksheet_ded1' => '',
            'worksheet_ded2' => '',
            'worksheet_ded3' => '',
            'worksheet_ded4' => '',
            'worksheet_ded5' => '',
        ];

        $w4 = $onboarding->formW4;
        if ($w4) {
            $form['single_or_married'] = $w4->single_or_married;
            $form['married_filing'] = $w4->married_filing;
            $form['head_of_household'] = $w4->head_of_household;
            $form['multiple_jobs_and_spouse_works'] = $w4->multiple_jobs_and_spouse_works;
            $form['qualifying_children'] = $w4->qualifying_children ?? '';
            $form['dependents'] = $w4->dependents ?? '';
            $form['total_amount'] = $w4->total_amount ?? '';
            $form['other_income'] = $w4->other_income ?? '';
            $form['deductions'] = $w4->deductions ?? '';
            $form['extra_withholding'] = $w4->extra_withholding ?? '';
            $form['employee_sign'] = $w4->employee_sign ?? $form['employee_sign'];
            $form['employee_date'] = $w4->employee_date ? $w4->employee_date->format('Y-m-d') : '';
            $form['employer_name'] = $w4->employer_name ?? $form['employer_name'];
            $form['date_of_employment'] = $w4->date_of_employment ?? $form['date_of_employment'];
            $form['worksheet_line1'] = $w4->worksheet_line1 ?? '';
            $form['worksheet_line2a'] = $w4->worksheet_line2a ?? '';
            $form['worksheet_line2b'] = $w4->worksheet_line2b ?? '';
            $form['worksheet_line2c'] = $w4->worksheet_line2c ?? '';
            $form['worksheet_line3'] = $w4->worksheet_line3 ?? '';
            $form['worksheet_line4'] = $w4->worksheet_line4 ?? '';
            $form['worksheet_ded1'] = $w4->worksheet_ded1 ?? '';
            $form['worksheet_ded2'] = $w4->worksheet_ded2 ?? '';
            $form['worksheet_ded3'] = $w4->worksheet_ded3 ?? '';
            $form['worksheet_ded4'] = $w4->worksheet_ded4 ?? '';
            $form['worksheet_ded5'] = $w4->worksheet_ded5 ?? '';
        }

        return to_json([
            'success' => true,
            'profile' => $profile,
            'social_security_number' => $socialSecurityNumber,
            'form' => $form,
            'company' => $onboarding->company ? [
                'name' => $onboarding->company->name,
                'employer_identification_number' => $onboarding->company->employer_identification_number ?? '',
                'address' => $onboarding->company->address ?? '',
            ] : null,
        ]);
    }

    /**
     * Save W-4 form (Step 5). Creates or updates w4_forms; updates onboarding_list (and employee) when name/address change.
     */
    public function saveW4(Request $request)
    {
        $request->validate([
            'onboardingId' => 'required|string',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'employee_sign' => 'required|string|max:255',
        ]);

        try {
            $onboardingNumber = Crypt::decrypt($request->onboardingId);
        } catch (\Exception $e) {
            return to_json([
                'message' => 'Invalid onboarding link.',
                'error' => 'Invalid onboarding link.',
            ], 400);
        }

        $onboarding = OnboardingList::with(['employee', 'company', 'formW4'])->where('onboarding_number', $onboardingNumber)->first();
        if (!$onboarding) {
            return to_json([
                'message' => 'Onboarding record not found.',
                'error' => 'Not found.',
            ], 404);
        }

        $isResubmitW4 = $onboarding->formW4 !== null;

        // Update onboarding list with name/address (single source of truth — do not duplicate in w4_forms)
        $onboarding->applicant_first_name = $request->first_name;
        $onboarding->applicant_last_name = $request->last_name;
        $onboarding->applicant_middle_initial = $request->middle_initial ?? $onboarding->applicant_middle_initial;
        $onboarding->applicant_address = $request->address ?? $onboarding->applicant_address;
        $onboarding->city = $request->city ?? $onboarding->city;
        $onboarding->state = $request->state ?? $onboarding->state;
        $onboarding->zip_code = $request->zip_code ?? $onboarding->zip_code;
        $onboarding->verified_w4 = ['saved_at' => now()->toIso8601String()];
        $onboarding->status = 'pending';
        if ($onboarding->process_id == 4) {
            $onboarding->process_id = 5;
        }
        $onboarding->save();

        $w4 = $onboarding->formW4 ?? new FormW4();
        $w4->employee_id = $onboarding->employee_id;
        $w4->onboarding_list_id = $onboarding->id;
        $w4->single_or_married = $request->filled('single_or_married') ? (int) $request->single_or_married : null;
        $w4->married_filing = $request->filled('married_filing') ? (int) $request->married_filing : null;
        $w4->head_of_household = $request->filled('head_of_household') ? (int) $request->head_of_household : null;
        $w4->multiple_jobs_and_spouse_works = $request->filled('multiple_jobs_and_spouse_works') ? (int) $request->multiple_jobs_and_spouse_works : null;
        $w4->qualifying_children = $request->qualifying_children ?? null;
        $w4->dependents = $request->dependents ?? null;
        $w4->total_amount = $request->total_amount ?? null;
        $w4->other_income = $request->other_income ?? null;
        $w4->deductions = $request->deductions ?? null;
        $w4->extra_withholding = $request->extra_withholding ?? null;
        $w4->worksheet_line1 = $request->worksheet_line1 ?? null;
        $w4->worksheet_line2a = $request->worksheet_line2a ?? null;
        $w4->worksheet_line2b = $request->worksheet_line2b ?? null;
        $w4->worksheet_line2c = $request->worksheet_line2c ?? null;
        $w4->worksheet_line3 = $request->worksheet_line3 ?? null;
        $w4->worksheet_line4 = $request->worksheet_line4 ?? null;
        $w4->worksheet_ded1 = $request->worksheet_ded1 ?? null;
        $w4->worksheet_ded2 = $request->worksheet_ded2 ?? null;
        $w4->worksheet_ded3 = $request->worksheet_ded3 ?? null;
        $w4->worksheet_ded4 = $request->worksheet_ded4 ?? null;
        $w4->worksheet_ded5 = $request->worksheet_ded5 ?? null;
        $w4->employee_sign = $request->employee_sign;
        $w4->employee_date = $request->employee_date ? \Carbon\Carbon::parse($request->employee_date) : now();
        $w4->employer_name = $onboarding->company?->name ?? $request->employer_name;
        $w4->date_of_employment = $request->date_of_employment ?? ($onboarding->doj ? \Carbon\Carbon::parse($onboarding->doj)->format('m-d-Y') : null);
        $w4->save();

        // Update employee record when name/address/SSN changed on W-4
        $employee = $onboarding->employee;
        if ($employee) {
            $employee->first_name = $request->first_name;
            $employee->last_name = $request->last_name;
            if ($request->has('address')) {
                $employee->street = $request->address;
            }
            if ($request->has('city')) {
                $employee->city = $request->city;
            }
            if ($request->has('state')) {
                $employee->state = $request->state;
            }
            if ($request->has('zip_code')) {
                $employee->zip = $request->zip_code;
            }
            if ($request->filled('social_security_number')) {
                $employee->ssn = $request->social_security_number;
            }
            $employee->save();
        }

        I9W4SubmittedNotificationService::notifyInternalW4($onboarding->fresh(['employee']), $isResubmitW4);

        return to_json([
            'message' => 'Form W-4 saved successfully.',
        ], 200);
    }

    /**
     * Save I9-W4 combined form data (wizard for staff member).
     */
    public function saveI9W4(Request $request)
    {
        $request->validate([
            'onboardingId' => 'required|string',
            'type' => 'required|string|in:i9,1099',
            'email' => 'required|email|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
        ]);

        try {
            $onboardingNumber = Crypt::decrypt($request->onboardingId);
        } catch (\Exception $e) {
            return to_json([
                'message' => 'Invalid onboarding link.',
                'error' => 'Invalid onboarding link.',
            ], 400);
        }

        $onboarding = OnboardingList::with(['employee'])->where('onboarding_number', $onboardingNumber)->first();
        if (!$onboarding) {
            return to_json([
                'message' => 'Onboarding record not found.',
                'error' => 'Not found.',
            ], 404);
        }

        // Create or update I9W4 record
        $i9w4 = $onboarding->i9W4 ?? new I9W4();
        $i9w4->employee_id = $onboarding->employee_id;
        $i9w4->onboarding_list_id = $onboarding->id;

        // Profile Information (Step 1)
        $i9w4->type = $request->type;
        $i9w4->email = $request->email;
        $i9w4->first_name = $request->first_name;
        $i9w4->middle_name = $request->middle_name ?? null;
        $i9w4->last_name = $request->last_name;
        $i9w4->street = $request->street ?? null;
        $i9w4->apt = $request->apt ?? null;
        $i9w4->city = $request->city ?? null;
        $i9w4->state = $request->state ?? null;
        $i9w4->zip = $request->zip ?? null;
        $i9w4->country = $request->country ?? null;
        $i9w4->phone = $request->phone ?? null;
        $i9w4->gender = $request->gender ?? null;
        $i9w4->birthdate = $request->birthdate ? \Carbon\Carbon::parse($request->birthdate) : null;
        $i9w4->ssn = $request->ssn ?? null;
        $i9w4->preferred_name = $request->preferred_name ?? null;

        // Groups (Step 2)
        $i9w4->workgroup_id = $request->workgroup_id ?? null;
        $i9w4->workgroup_name = $request->workgroup_name ?? null;

        // Current Employment (Step 3)
        $i9w4->job_title = $request->job_title ?? null;
        $i9w4->employment_type = $request->employment_type ?? null;
        $i9w4->start_date = $request->start_date ? \Carbon\Carbon::parse($request->start_date) : null;
        $i9w4->manager_id = $request->manager_id ?? null;
        $i9w4->manager = $request->manager ?? null;
        $i9w4->salary = $request->salary ?? null;
        $i9w4->salary_period = $request->salary_period ?? 'annually';

        $i9w4->save();

        // Also update the onboarding_list with basic info
        $onboarding->applicant_first_name = $request->first_name;
        $onboarding->applicant_last_name = $request->last_name;
        $onboarding->applicant_middle_initial = $request->middle_name ?? $onboarding->applicant_middle_initial;
        $onboarding->applicant_email = $request->email;
        $onboarding->applicant_address = $request->street ?? $onboarding->applicant_address;
        $onboarding->apt_number = $request->apt ?? $onboarding->apt_number;
        $onboarding->city = $request->city ?? $onboarding->city;
        $onboarding->state = $request->state ?? $onboarding->state;
        $onboarding->zip_code = $request->zip ?? $onboarding->zip_code;
        $onboarding->applicant_contact_number = $request->phone ?? $onboarding->applicant_contact_number;
        $onboarding->dob = $request->birthdate ?? $onboarding->dob;
        $onboarding->i9_w4_completed = true;
        $onboarding->i9_w4_date = now();
        $onboarding->save();

        // Update employee record if exists
        $employee = $onboarding->employee;
        if ($employee) {
            $employee->first_name = $request->first_name;
            $employee->last_name = $request->last_name;
            $employee->email = $request->email;
            if ($request->has('street')) {
                $employee->street = $request->street;
            }
            if ($request->has('city')) {
                $employee->city = $request->city;
            }
            if ($request->has('state')) {
                $employee->state = $request->state;
            }
            if ($request->has('zip')) {
                $employee->zip = $request->zip;
            }
            if ($request->has('phone')) {
                $employee->phone = $request->phone;
            }
            if ($request->filled('ssn')) {
                $employee->ssn = $request->ssn;
            }
            $employee->save();
        }

        return to_json([
            'message' => 'Staff member data saved successfully.',
            'data' => $i9w4,
        ], 200);
    }

    /**
     * Get I9-W4 combined form data for the wizard.
     */
    public function getI9W4Data(Request $request)
    {
        $request->validate([
            'onboardingId' => 'required|string',
        ]);

        try {
            $onboardingNumber = Crypt::decrypt($request->onboardingId);
        } catch (\Exception $e) {
            return to_json([
                'message' => 'Invalid onboarding link.',
                'error' => 'Invalid onboarding link.',
            ], 400);
        }

        $onboarding = OnboardingList::with(['employee.workgroup', 'i9W4'])->where('onboarding_number', $onboardingNumber)->first();
        if (!$onboarding) {
            return to_json([
                'message' => 'Onboarding record not found.',
                'error' => 'Not found.',
            ], 404);
        }

        $i9w4 = $onboarding->i9W4;

        // Return existing I9W4 data if available, otherwise return onboarding/employee data
        if ($i9w4) {
            return to_json($i9w4);
        }

        // Build data from onboarding/employee records
        $emp = $onboarding->employee;
        $data = [
            'type' => 'i9',
            'email' => $onboarding->applicant_email ?? $emp?->email,
            'firstName' => $onboarding->applicant_first_name ?? $emp?->first_name,
            'middleName' => $onboarding->applicant_middle_initial ?? $emp?->middle_name,
            'lastName' => $onboarding->applicant_last_name ?? $emp?->last_name,
            'street' => $onboarding->applicant_address ?? $emp?->street,
            'apt' => $onboarding->apt_number ?? $emp?->apt_number,
            'city' => $onboarding->city ?? $emp?->city,
            'state' => $onboarding->state ?? $emp?->state,
            'zip' => $onboarding->zip_code ?? $emp?->zip,
            'country' => null,
            'phone' => $onboarding->applicant_contact_number ?? $emp?->phone,
            'gender' => $onboarding->applicant_gender ?? null,
            'birthdate' => $onboarding->dob ?? $emp?->dob,
            'ssn' => $emp?->ssn,
            'preferredName' => $emp?->preferred_name,
            'workgroupId' => $emp?->workgroup_id,
            'workgroupName' => $emp?->workgroup?->name,
            'jobTitle' => $emp?->title,
            'employmentType' => $emp?->type,
            'startDate' => $onboarding->doj ?? $emp?->doj,
            'manager' => $emp?->supervisor_id,
            'salary' => $emp?->pay,
            'salaryPeriod' => $emp?->salary_period ?? 'annually',
        ];

        return to_json($data);
    }

    /**
     * Get benefit enrollment form data. Returns employee/profile info and any saved benefit form data.
     */
    public function getBenefitData(Request $request)
    {
        $request->validate([
            'onboardingId' => 'required_without:employeeId|string',
            'employeeId' => 'required_without:onboardingId|string',
        ]);

        try {
            if ($request->onboardingId) {
                $onboardingNumber = Crypt::decrypt($request->onboardingId);
            } else {
                $employeeId = Crypt::decrypt($request->employeeId);
            }
        } catch (\Exception $e) {
            return to_json([
                'message' => 'Invalid onboarding link.',
                'error' => 'Invalid onboarding link.',
            ], 400);
        }
        if ($request->onboardingId) {
            $onboarding = OnboardingList::with(['employee', 'company', 'benefitEnrollment.dependents'])->where('onboarding_number', $onboardingNumber)->first();
            if (!$onboarding) {
                return to_json([
                    'message' => 'Onboarding record not found.',
                    'error' => 'Not found.',
                ], 404);
            }
            $employee = $onboarding->employee;
            $enrollment = $onboarding->benefitEnrollment ??  $employee->benefitEnrollment;
            $doj = $onboarding->doj;
        } else {
            $employee = Employee::find($employeeId);
            if (!$employee) {
                return to_json([
                    'message' => 'Employee not found.',
                    'error' => 'Not found.',
                ], 404);
            }
            $onboarding = OnboardingList::where('employee_id', $employee->id)->first();
            $enrollment = $onboarding->benefitEnrollment ??  $employee->benefitEnrollment;
            $doj = $employee->hire_date;
        }

        $state = BenefitElection::resolveBenefitStateCode($onboarding, $employee);
        $profile = [
            'last_name' => $onboarding->applicant_last_name ?? $employee->last_name ?? '',
            'first_name' => $onboarding->applicant_first_name ?? $employee->first_name ?? '',
            'middle_initial' => $onboarding->applicant_middle_initial ?? $employee->middle_name ?? '',
            'dob' => isset($onboarding->dob) ? \Carbon\Carbon::parse($onboarding->dob)->format('Y-m-d') : (isset($employee->dob) ? \Carbon\Carbon::parse($employee->dob)->format('Y-m-d') : ''),
            'social_security_number' => $employee->ssn ?? '',
            'address' => $onboarding->applicant_address ?? $employee->street ?? '',
            'city' => $onboarding->city ?? $employee->city ?? '',
            'state' => $onboarding->state ?? $employee->state ?? '',
            'zip' => $onboarding->zip_code ?? $employee->zip ?? '',
            'email' => $onboarding->applicant_email ?? $employee->email ?? '',
            'pos_name' => $employee->pos_name ?? '',
        ];

        $saved = [];


        if ($enrollment) {
            // Only benefit-specific fields; name/address/dob/ssn/email etc. come from onboarding_list (profile)
            $saved = $enrollment->only([
                'gender',
                'salary',
                'hire_date',
                'medical_plan',
                'medical_waive_reason',
                'dental',
                'vision',
                'life_option',
                'emp_life_amount',
                'spouse_life_amount',
                'child_life_amount',
                'emp_life_cost',
                'spouse_life_cost',
                'child_life_cost',
                'tax_method',
                'employee_sign',
                'employee_date',
                'benefit_acknowledgment',
                'waive_ack_point_1',
                'waive_ack_point_2',
                'waive_ack_name',
                'waive_ack_signature',
                'waive_ack_date',
            ]);
            foreach ($saved as $k => $v) {
                if ($v instanceof \Carbon\Carbon || $v instanceof \DateTimeInterface) {
                    $saved[$k] = $v->format('Y-m-d');
                }
            }
            $saved['dependents'] = $enrollment->dependents->map(function ($d) {
                $row = $d->only(['last_name', 'first_name', 'gender', 'dob', 'ssn', 'relationship', 'medical', 'dental', 'vision']);
                if (isset($row['dob']) && $row['dob']) {
                    $row['dob'] = $row['dob'] instanceof \DateTimeInterface ? $row['dob']->format('Y-m-d') : $row['dob'];
                }
                return $row;
            })->toArray();
        } else {
            $saved['hire_date'] = Carbon::parse($doj)->format('Y-m-d');
        }

        if ($request->employeeId) {
            $saved['benefit_submitted_at'] = $employee->benefit_submitted_at ? Carbon::parse($employee->benefit_submitted_at)->format('Y-m-d') : null;
        }

        return to_json([
            'success' => true,
            'profile' => $profile,
            'form' => $saved,
            'state' => $state,
        ]);
    }

    /**
     * Save benefits enrollment form. Uses benefit_enrollments table; updates existing record if present (no duplicate).
     */
    public function saveBenefitsEnroll(Request $request)
    {
        $request->validate([
            'onboardingId' => 'required_without:employeeId|string',
            'employeeId' => 'required_without:onboardingId|string',
        ]);
        if ($request->onboardingId) {
            try {
                $onboardingNumber = Crypt::decrypt($request->onboardingId);
            } catch (\Exception $e) {
                return to_json([
                    'message' => 'Invalid onboarding link.',
                    'error' => 'Invalid onboarding link.',
                ], 400);
            }

            $onboarding = OnboardingList::where('onboarding_number', $onboardingNumber)->first();
            if (!$onboarding) {
                return to_json([
                    'message' => 'Onboarding record not found.',
                    'error' => 'Not found.',
                ], 404);
            }
            $employee = $onboarding->employee;
        } else {
            $employee = Employee::find(decrypt($request->employeeId));
            if (!$employee) {
                return to_json([
                    'message' => 'Employee not found.',
                    'error' => 'Not found.',
                ], 404);
            }
            $onboarding = OnboardingList::where('employee_id', $employee->id)->first();
        }
        $payload = $request->except(['onboardingId', '_token']);
        $dependents = $payload['dependents'] ?? [];
        unset($payload['dependents']);

        // Only save benefit-specific fields; name/address/dob/ssn/email stay in onboarding_list (no duplicate)
        $enrollmentData = array_intersect_key($payload, array_flip([
            'gender',
            'salary',
            'hire_date',
            'medical_plan',
            'medical_waive_reason',
            'dental',
            'vision',
            'life_option',
            'emp_life_amount',
            'spouse_life_amount',
            'child_life_amount',
            'emp_life_cost',
            'spouse_life_cost',
            'child_life_cost',
            'tax_method',
            'employee_sign',
            'employee_date',
        ]));
        $enrollmentData['benefit_acknowledgment'] = 'qualify';

        DB::transaction(function () use ($onboarding, $employee, $enrollmentData, $dependents, $request) {

            $enrollmentData['employee_id'] = $employee->id;
            if ($onboarding) {
                $enrollment = BenefitEnrollment::updateOrCreate(
                    ['onboarding_list_id' => $onboarding->id],
                    $enrollmentData
                );
                $onboarding->update([
                    'applicant_last_name' => $request->last_name ?? '',
                    'applicant_first_name' => $request->first_name ?? '',
                    'applicant_middle_initial' => $request->middle_initial ?? '',
                    'dob' => $request->dob ?? '',
                    'applicant_address' => $request->address ?? '',
                    'city' => $request->city ?? '',
                    'state' => $request->state ?? '',
                    'zip_code' => $request->zip ?? '',
                ]);
                $employee->update([
                    'last_name' => $request->last_name ?? '',
                    'ssn' => $request->ssn ?? '',
                    'first_name' => $request->first_name ?? '',
                    'middle_name' => $request->middle_initial ?? '',
                    'dob' => $request->dob ?? '',
                    'street' => $request->address ?? '',
                    'city' => $request->city ?? '',
                    'state' => $request->state ?? '',
                    'zip' => $request->zip ?? '',
                ]);
            } else {
                $enrollmentData['onboarding_list_id'] = 0;
                $enrollment = BenefitEnrollment::updateOrCreate(
                    ['employee_id' => $employee->id],
                    $enrollmentData
                );

                $employee->update([
                    'last_name' => $request->last_name ?? '',
                    'ssn' => $request->ssn ?? '',
                    'first_name' => $request->first_name ?? '',
                    'middle_name' => $request->middle_initial ?? '',
                    'dob' => $request->dob ?? '',
                    'street' => $request->address ?? '',
                    'city' => $request->city ?? '',
                    'state' => $request->state ?? '',
                    'zip' => $request->zip ?? '',
                ]);
            }

            $enrollment->dependents()->delete();
            foreach (is_array($dependents) ? $dependents : [] as $dep) {
                $enrollment->dependents()->create([
                    'last_name' => $dep['last_name'] ?? null,
                    'first_name' => $dep['first_name'] ?? null,
                    'gender' => $dep['gender'] ?? null,
                    'dob' => !empty($dep['dob']) ? $dep['dob'] : null,
                    'ssn' => $dep['ssn'] ?? null,
                    'relationship' => $dep['relationship'] ?? null,
                    'medical' => (int) ($dep['medical'] ?? 0),
                    'dental' => (int) ($dep['dental'] ?? 0),
                    'vision' => (int) ($dep['vision'] ?? 0),
                ]);
            }
        });

        if ($request->onboardingId && $onboarding->process_id == 5) {
            $onboarding->process_id = 6;
            $onboarding->save();
        }

        if ($request->employeeId) {
            $employee->benefit_submitted_at = now();
            $employee->save();
            $this->storeBenefitAcknowledgmentPdfAsEmployeeDocument($onboarding, $employee);
            $this->storeBenefitPdfAsEmployeeDocument($onboarding, $employee);
        }
        return to_json([
            'message' => 'Benefits enrollment saved successfully.',
        ], 200);
    }

    /**
     * Save benefits decline acknowledgment. Skips the enrollment form.
     */
    public function saveBenefitsDecline(Request $request)
    {
        $request->validate([
            'onboardingId' => 'required_without:employeeId|string',
            'employeeId' => 'required_without:onboardingId|string',
            // 'waive_ack_name' => 'nullable|string|max:255',
            'waive_ack_signature' => 'nullable|string|max:255',
            'waive_ack_date' => 'nullable|date',
        ]);

        if ($request->onboardingId) {
            try {
                $onboardingNumber = Crypt::decrypt($request->onboardingId);
            } catch (\Exception $e) {
                return to_json([
                    'message' => 'Invalid onboarding link.',
                    'error' => 'Invalid onboarding link.',
                ], 400);
            }

            $onboarding = OnboardingList::where('onboarding_number', $onboardingNumber)->first();
            if (!$onboarding) {
                return to_json([
                    'message' => 'Onboarding record not found.',
                    'error' => 'Not found.',
                ], 404);
            }
            $employee = $onboarding->employee;
        } else {
            $employee = Employee::find(decrypt($request->employeeId));
            if (!$employee) {
                return to_json([
                    'message' => 'Employee not found.',
                    'error' => 'Not found.',
                ], 404);
            }
            $onboarding = OnboardingList::where('employee_id', $employee->id)->first();
        }

        DB::transaction(function () use ($onboarding, $employee, $request) {
            $enrollmentData = [
                'employee_id' => $employee->id,
                'benefit_acknowledgment' => 'decline',
            ];

            if ($request->filled('waive_ack_name')) {
                $enrollmentData['waive_ack_name'] = $request->waive_ack_name;
            }
            if ($request->filled('waive_ack_signature')) {
                $enrollmentData['waive_ack_signature'] = $request->waive_ack_signature;
            }
            if ($request->filled('waive_ack_date')) {
                $enrollmentData['waive_ack_date'] = $request->waive_ack_date;
            }

            if ($onboarding) {
                BenefitEnrollment::updateOrCreate(
                    ['onboarding_list_id' => $onboarding->id],
                    $enrollmentData
                );
            } else {
                $enrollmentData['onboarding_list_id'] = 0;
                BenefitEnrollment::updateOrCreate(
                    ['employee_id' => $employee->id],
                    $enrollmentData
                );
            }
        });

        if ($request->onboardingId && $onboarding->process_id == 5) {
            $onboarding->process_id = 6;
            $onboarding->save();
        }

        if ($request->employeeId) {
            $employee->benefit_submitted_at = now();
            $employee->save();
            $this->storeBenefitAcknowledgmentPdfAsEmployeeDocument($onboarding, $employee);
            $this->storeBenefitPdfAsEmployeeDocument($onboarding, $employee);
        }

        return to_json([
            'message' => 'Benefits decline saved successfully.',
        ], 200);
    }

    /**
     * Save benefits waive acknowledgement form (step 2 before enrollment).
     */
    public function saveBenefitsWaiveAck(Request $request)
    {
        $request->validate([
            'onboardingId' => 'required_without:employeeId|string',
            'employeeId' => 'required_without:onboardingId|string',
            'waive_ack_name' => 'required|string|max:255',
            'waive_ack_signature' => 'required|string|max:255',
            'waive_ack_date' => 'required|date',
        ]);

        if ($request->onboardingId) {
            try {
                $onboardingNumber = Crypt::decrypt($request->onboardingId);
            } catch (\Exception $e) {
                return to_json([
                    'message' => 'Invalid onboarding link.',
                    'error' => 'Invalid onboarding link.',
                ], 400);
            }

            $onboarding = OnboardingList::where('onboarding_number', $onboardingNumber)->first();
            if (!$onboarding) {
                return to_json([
                    'message' => 'Onboarding record not found.',
                    'error' => 'Not found.',
                ], 404);
            }
            $employee = $onboarding->employee;
        } else {
            $employee = Employee::find(decrypt($request->employeeId));
            if (!$employee) {
                return to_json([
                    'message' => 'Employee not found.',
                    'error' => 'Not found.',
                ], 404);
            }
            $onboarding = OnboardingList::where('employee_id', $employee->id)->first();
        }

        $enrollmentData = [
            'employee_id' => $employee->id,
            'benefit_acknowledgment' => 'qualify',
            'waive_ack_name' => $request->waive_ack_name,
            'waive_ack_signature' => $request->waive_ack_signature,
            'waive_ack_date' => $request->waive_ack_date,
        ];

        DB::transaction(function () use ($onboarding, $employee, $enrollmentData) {
            if ($onboarding) {
                BenefitEnrollment::updateOrCreate(
                    ['onboarding_list_id' => $onboarding->id],
                    $enrollmentData
                );
            } else {
                $enrollmentData['onboarding_list_id'] = 0;
                BenefitEnrollment::updateOrCreate(
                    ['employee_id' => $employee->id],
                    $enrollmentData
                );
            }
        });

        return to_json([
            'message' => 'Benefits acknowledgement saved successfully.',
        ], 200);
    }

    public function updateOnboardingProcess(Request $request)
    {
        $request->validate([
            'onboarding_id' => 'required|string',
        ]);

        try {
            $onboarding = OnboardingList::where('onboarding_number', decrypt($request->onboarding_id))->first();

            $onboarding->process_id = $onboarding->process_id == 0 ? 1 : $onboarding->process_id;
            $onboarding->save();
            if (!$onboarding) {
                return to_json([
                    'message' => 'Onboarding record not found.',
                    'error' => 'Not found.',
                ], 404);
            }
            return to_json([
                'message' => 'Onboarding process updated successfully',
                'id' => $onboarding->id,
            ], 200);
        } catch (\Exception $e) {
            return to_json([
                'message' => 'Failed to update onboarding process',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Direct Deposit data for Step 7
     */
    public function getDirectDepositData(Request $request)
    {
        $request->validate([
            'onboardingId' => 'required|string',
        ]);

        try {
            $onboardingNumber = Crypt::decrypt($request->onboardingId);
        } catch (\Exception $e) {
            return to_json([
                'message' => 'Invalid onboarding ID.',
                'error' => 'Invalid onboarding ID.',
            ], 400);
        }

        $onboarding = OnboardingList::with(['directDeposit', 'company', 'employee'])
            ->where('onboarding_number', $onboardingNumber)
            ->first();

        if (!$onboarding) {
            return to_json([
                'message' => 'Onboarding record not found.',
                'error' => 'Not found.',
            ], 404);
        }

        $directDeposit = $onboarding->directDeposit;

        return to_json([
            'success' => true,
            'direct_deposit' => $directDeposit ? [
                'id' => $directDeposit->id,
                'acct_type_1' => $directDeposit->acct_type_1,
                'bank_name_1' => $directDeposit->bank_name_1,
                'bank_routing_1' => $directDeposit->bank_routing_1,
                'account_number_1' => $directDeposit->account_number_1,
                'deposit_amount_1' => $directDeposit->deposit_amount_1,
                'deposit_type_1' => $directDeposit->deposit_type_1 ?? 'percentage',
                'acct_type_2' => $directDeposit->acct_type_2,
                'bank_name_2' => $directDeposit->bank_name_2,
                'bank_routing_2' => $directDeposit->bank_routing_2,
                'account_number_2' => $directDeposit->account_number_2,
                'deposit_amount_2' => $directDeposit->deposit_amount_2,
                'deposit_type_2' => $directDeposit->deposit_type_2,
                'signature' => $directDeposit->signature,
                'printed_name' => $onboarding->employee->check_name ?? '',
                'employee_id' => $directDeposit->employee_id,
                'date' => $directDeposit->date?->format('Y-m-d'),
            ] : [
                'printed_name' => $onboarding->employee->check_name ?? '',
            ],
            'company_name' => $onboarding->company->name ?? '',
            'company_code' => $onboarding->company->store_number ?? '',
            'employee_id' => $onboarding->employee->employee_id ?? '',
        ]);
    }

    /**
     * Save Direct Deposit data (Step 7)
     */
    public function saveDirectDeposit(Request $request)
    {
        $request->validate([
            'onboardingId' => 'required|string',
            'acct_type_1' => 'required|in:checking,savings',
            'bank_name_1' => 'required|string|max:255',
            'bank_routing_1' => 'required|string|max:50',
            'account_number_1' => 'required|string|max:50',
            'deposit_amount_1' => 'required|max:50',
            'deposit_type_1' => 'nullable|in:percentage,fixed',
            'acct_type_2' => 'nullable|in:checking,savings',
            'bank_name_2' => 'nullable|string|max:255',
            'bank_routing_2' => 'nullable|string|max:50',
            'account_number_2' => 'nullable|string|max:50',
            'deposit_amount_2' => 'nullable|max:50',
            'deposit_type_2' => 'nullable|in:percentage,fixed',
            'signature' => 'required|string|max:255',
            'printed_name' => 'required|string|max:255',
            'date' => 'required|date',
        ]);

        try {
            $onboardingNumber = Crypt::decrypt($request->onboardingId);
        } catch (\Exception $e) {
            return to_json([
                'message' => 'Invalid onboarding ID.',
                'error' => 'Invalid onboarding ID.',
            ], 400);
        }

        $onboarding = OnboardingList::where('onboarding_number', $onboardingNumber)->first();
        if (!$onboarding) {
            return to_json([
                'message' => 'Onboarding record not found.',
                'error' => 'Not found.',
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Update or create direct deposit record
            DirectDeposit::updateOrCreate(
                ['onboarding_list_id' => $onboarding->id],
                [
                    'company_code' => $request->company_code,
                    'acct_type_1' => $request->acct_type_1,
                    'bank_name_1' => $request->bank_name_1,
                    'bank_routing_1' => $request->bank_routing_1,
                    'account_number_1' => $request->account_number_1,
                    'deposit_amount_1' => $request->deposit_amount_1,
                    'deposit_type_1' => $request->deposit_type_1 ?? 'percentage',
                    'acct_type_2' => $request->acct_type_2,
                    'bank_name_2' => $request->bank_name_2,
                    'bank_routing_2' => $request->bank_routing_2,
                    'account_number_2' => $request->account_number_2,
                    'deposit_amount_2' => $request->deposit_amount_2,
                    'deposit_type_2' => $request->deposit_type_2,
                    'signature' => $request->signature,
                    'printed_name' => $request->printed_name,
                    'employee_id' => $request->employee_id,
                    'date' => $request->date,
                ]
            );

            // Update process_id to move to step 8 (Emergency Contact)
            if ($onboarding->process_id == 6) {
                $onboarding->process_id = 7;
                $onboarding->employee->update(['check_name' => $request->printed_name]);
                $onboarding->save();
            }

            DB::commit();

            return to_json([
                'success' => true,
                'message' => 'Direct deposit information saved successfully.',
                'next_step' => 8,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'success' => false,
                'message' => 'Failed to save direct deposit information.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Emergency Contact data for Step 8
     */
    public function getEmergencyContactData(Request $request)
    {
        $request->validate([
            'onboardingId' => 'required|string',
        ]);

        try {
            $onboardingNumber = Crypt::decrypt($request->onboardingId);
        } catch (\Exception $e) {
            return to_json([
                'message' => 'Invalid onboarding ID.',
                'error' => 'Invalid onboarding ID.',
            ], 400);
        }

        $onboarding = OnboardingList::with(['emergencyContact', 'employee'])
            ->where('onboarding_number', $onboardingNumber)
            ->first();

        if (!$onboarding) {
            return to_json([
                'message' => 'Onboarding record not found.',
                'error' => 'Not found.',
            ], 404);
        }

        // Get data from onboarding_list (pre-filled data from Step 3)
        $profile = [
            'e_emp_name' => $onboarding->applicant_last_name ?? '',
            'e_emp_first_name' => $onboarding->applicant_first_name ?? '',
            'e_emp_middle_initial' => $onboarding->applicant_middle_initial ?? '',
            'e_home_phone' => $onboarding->applicant_contact_number ?? '',
            'e_cell_phone' => $onboarding->applicant_contact_number ?? '',
            'e_home_email' => $onboarding->applicant_email ?? '',
            'e_address_street' => $onboarding->applicant_address ?? '',
            'e_address_city' => $onboarding->city ?? '',
            'e_address_state' => $onboarding->state ?? '',
        ];

        // Split emergency contact name from onboarding_list
        $fullName = $onboarding->emergency_contact_name ?? '';
        $nameParts = explode(' ', trim($fullName));
        $firstName = $nameParts[0] ?? '';
        $lastName = count($nameParts) > 1 ? implode(' ', array_slice($nameParts, 1)) : '';

        $emergencyContact = $onboarding->emergencyContact;

        $formData = null;
        if ($emergencyContact) {
            $formData = [
                'e_emergency_contact_first_name' => $emergencyContact->e_emergency_contact_first_name,
                'e_emergency_contact_last_name' => $emergencyContact->e_emergency_contact_last_name,
                'e_emergency_contact_relationship' => $emergencyContact->e_emergency_contact_relationship,
                'e_emergency_contact_phone_home' => $emergencyContact->e_emergency_contact_phone_home,
                'e_emergency_contact_phone_cell' => $emergencyContact->e_emergency_contact_phone_cell,
                'e_emergency_contact_phone_work' => $emergencyContact->e_emergency_contact_phone_work,
                'e_emergency_contact_email' => $emergencyContact->e_emergency_contact_email,
                'e_emergency_contact2_first_name' => $emergencyContact->e_emergency_contact2_first_name,
                'e_emergency_contact2_last_name' => $emergencyContact->e_emergency_contact2_last_name,
                'e_emergency_contact2_relationship' => $emergencyContact->e_emergency_contact2_relationship,
                'e_emergency_contact2_phone_home' => $emergencyContact->e_emergency_contact2_phone_home,
                'e_emergency_contact2_phone_cell' => $emergencyContact->e_emergency_contact2_phone_cell,
                'e_emergency_contact2_phone_work' => $emergencyContact->e_emergency_contact2_phone_work,
                'e_emergency_contact2_email' => $emergencyContact->e_emergency_contact2_email,
                'e_emergency_contact2_hospital' => $emergencyContact->e_emergency_contact2_hospital,
                'e_insurance_company' => $emergencyContact->e_insurance_company,
                'e_insurance_policy_number' => $emergencyContact->e_insurance_policy_number,
                'e_signature' => $emergencyContact->e_signature,
                'e_signature_date' => $emergencyContact->e_signature_date?->format('Y-m-d'),
            ];
        } else {
            // Use data from onboarding_list as defaults
            $formData = [
                'e_emergency_contact_first_name' => $firstName,
                'e_emergency_contact_last_name' => $lastName,
                'e_emergency_contact_relationship' => $onboarding->emergency_contact_relationship ?? '',
                'e_emergency_contact_phone_home' => $onboarding->emergency_contact_phone ?? '',
                'e_emergency_contact_phone_cell' => '',
                'e_emergency_contact_phone_work' => '',
                'e_emergency_contact_email' => '',
                'e_emergency_contact2_first_name' => '',
                'e_emergency_contact2_last_name' => '',
                'e_emergency_contact2_relationship' => '',
                'e_emergency_contact2_phone_home' => '',
                'e_emergency_contact2_phone_cell' => '',
                'e_emergency_contact2_phone_work' => '',
                'e_emergency_contact2_email' => '',
                'e_emergency_contact2_hospital' => '',
                'e_insurance_company' => '',
                'e_insurance_policy_number' => '',
                'e_signature' => $onboarding->applicant_first_name ?? '',
                'e_signature_date' => date('Y-m-d'),
            ];
        }

        return to_json([
            'success' => true,
            'profile' => $profile,
            'form' => $formData,
        ]);
    }

    /**
     * Save Emergency Contact data (Step 8)
     */
    public function saveEmergencyContact(Request $request)
    {
        $request->validate([
            'onboardingId' => 'required|string',
            'e_emergency_contact_first_name' => 'required|string|max:255',
            'e_emergency_contact_last_name' => 'required|string|max:255',
            'e_emergency_contact_relationship' => 'required|string|max:255',
            'e_emergency_contact_phone_home' => 'nullable|string|max:255|required_without:e_emergency_contact_phone_cell',
            'e_emergency_contact_phone_cell' => 'nullable|string|max:255|required_without:e_emergency_contact_phone_home',
            'e_signature' => 'required|string|max:255',
            'e_signature_date' => 'required|date',
        ], [
            'e_emergency_contact_phone_home.required_without' => 'Phone: Home or Phone: Cell is required for Primary Emergency Contact.',
            'e_emergency_contact_phone_cell.required_without' => 'Phone: Home or Phone: Cell is required for Primary Emergency Contact.',
        ]);

        try {
            $onboardingNumber = Crypt::decrypt($request->onboardingId);
        } catch (\Exception $e) {
            return to_json([
                'message' => 'Invalid onboarding ID.',
                'error' => 'Invalid onboarding ID.',
            ], 400);
        }

        $onboarding = OnboardingList::where('onboarding_number', $onboardingNumber)->first();
        if (!$onboarding) {
            return to_json([
                'message' => 'Onboarding record not found.',
                'error' => 'Not found.',
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Update or create emergency contact record
            EmergencyContact::updateOrCreate(
                ['onboarding_list_id' => $onboarding->id],
                [
                    'employee_id' => $onboarding->employee_id,
                    'e_emergency_contact_first_name' => $request->e_emergency_contact_first_name,
                    'e_emergency_contact_last_name' => $request->e_emergency_contact_last_name,
                    'e_emergency_contact_relationship' => $request->e_emergency_contact_relationship,
                    'e_emergency_contact_phone_home' => $request->e_emergency_contact_phone_home,
                    'e_emergency_contact_phone_cell' => $request->e_emergency_contact_phone_cell,
                    'e_emergency_contact_phone_work' => $request->e_emergency_contact_phone_work,
                    'e_emergency_contact_email' => $request->e_emergency_contact_email,
                    'e_emergency_contact2_first_name' => $request->e_emergency_contact2_first_name,
                    'e_emergency_contact2_last_name' => $request->e_emergency_contact2_last_name,
                    'e_emergency_contact2_relationship' => $request->e_emergency_contact2_relationship,
                    'e_emergency_contact2_phone_home' => $request->e_emergency_contact2_phone_home,
                    'e_emergency_contact2_phone_cell' => $request->e_emergency_contact2_phone_cell,
                    'e_emergency_contact2_phone_work' => $request->e_emergency_contact2_phone_work,
                    'e_emergency_contact2_email' => $request->e_emergency_contact2_email,
                    'e_emergency_contact2_hospital' => $request->e_emergency_contact2_hospital,
                    'e_insurance_company' => $request->e_insurance_company,
                    'e_insurance_policy_number' => $request->e_insurance_policy_number,
                    'e_signature' => $request->e_signature,
                    'e_signature_date' => $request->e_signature_date,
                ]
            );

            // Update onboarding_list emergency contact fields (but only if data exists in onboarding_list)
            // This keeps the basic emergency contact info in onboarding_list for backward compatibility
            if ($onboarding->emergency_contact_name || $onboarding->emergency_contact_phone || $onboarding->emergency_contact_relationship) {
                $onboarding->emergency_contact_name = trim($request->e_emergency_contact_first_name . ' ' . $request->e_emergency_contact_last_name);
                $onboarding->emergency_contact_phone = $request->e_emergency_contact_phone_home ?? $request->e_emergency_contact_phone_cell;
                $onboarding->emergency_contact_relationship = $request->e_emergency_contact_relationship;
            }

            // Update process_id to move to next step
            if ($onboarding->process_id == 7) {
                $onboarding->process_id = 8;
                $onboarding->save();
            }

            DB::commit();

            return to_json([
                'success' => true,
                'message' => 'Emergency contact information saved successfully.',
                'next_step' => 9,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'success' => false,
                'message' => 'Failed to save emergency contact information.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Final Submission data for Step 9
     */
    public function getFinalSubmissionData(Request $request)
    {
        $request->validate([
            'onboardingId' => 'required|string',
        ]);

        try {
            $onboardingNumber = Crypt::decrypt($request->onboardingId);
        } catch (\Exception $e) {
            return to_json([
                'message' => 'Invalid onboarding ID.',
                'error' => 'Invalid onboarding ID.',
            ], 400);
        }

        $onboarding = OnboardingList::with(['finalSubmission'])
            ->where('onboarding_number', $onboardingNumber)
            ->first();

        if (!$onboarding) {
            return to_json([
                'message' => 'Onboarding record not found.',
                'error' => 'Not found.',
            ], 404);
        }

        $finalSubmission = $onboarding->finalSubmission;

        $formData = null;
        if ($finalSubmission) {
            $formData = [
                'check_confirmation' => $finalSubmission->check_confirmation,
                'certification_complete' => $finalSubmission->certification_complete,
                'work_authorization_confirmed' => $finalSubmission->work_authorization_confirmed,
                'false_info_acknowledgment' => $finalSubmission->false_info_acknowledgment,
                'electronic_signature_consent' => $finalSubmission->electronic_signature_consent,
                'signature' => $finalSubmission->signature,
                'signature_date' => $finalSubmission->signature_date?->format('Y-m-d'),
            ];
        } else {
            $formData = [
                'check_confirmation' => false,
                'certification_complete' => false,
                'work_authorization_confirmed' => false,
                'false_info_acknowledgment' => false,
                'electronic_signature_consent' => false,
                'signature' => $onboarding->applicant_first_name . ' ' . $onboarding->applicant_last_name,
                'signature_date' => date('Y-m-d'),
            ];
        }

        return to_json([
            'success' => true,
            'form' => $formData,
            'applicant_name' => trim($onboarding->applicant_first_name . ' ' . $onboarding->applicant_last_name),
        ]);
    }

    /**
     * Save Final Submission data (Step 9)
     */
    public function saveFinalSubmission(Request $request)
    {
        $request->validate([
            'onboardingId' => 'required|string',
            'check_confirmation' => 'required|boolean',
            'signature' => 'required|string|max:255',
            'signature_date' => 'required|date',
        ]);

        try {
            $onboardingNumber = Crypt::decrypt($request->onboardingId);
        } catch (\Exception $e) {
            return to_json([
                'message' => 'Invalid onboarding ID.',
                'error' => 'Invalid onboarding ID.',
            ], 400);
        }

        $onboarding = OnboardingList::where('onboarding_number', $onboardingNumber)->first();
        if (!$onboarding) {
            return to_json([
                'message' => 'Onboarding record not found.',
                'error' => 'Not found.',
            ], 404);
        }

        DB::beginTransaction();
        try {
            $finalSubmissionData = FinalSubmission::updateOrCreate(
                ['onboarding_list_id' => $onboarding->id],
                [
                    'employee_id' => $onboarding->employee_id,
                    'check_confirmation' => $request->check_confirmation,
                    'certification_complete' => $request->certification_complete ?? false,
                    'work_authorization_confirmed' => $request->work_authorization_confirmed ?? false,
                    'false_info_acknowledgment' => $request->false_info_acknowledgment ?? false,
                    'electronic_signature_consent' => $request->electronic_signature_consent ?? false,
                    'signature' => $request->signature,
                    'signature_date' => $request->signature_date,
                ]
            );

            // Update onboarding_list with final submission status
            $onboarding->final_submission_completed = true;
            $onboarding->final_submission_date = now();

            // Update process_id to move to completion (process_id = 9 means completed)
            if ($onboarding->process_id == 8) {
                $onboarding->process_id = 9;
            }

            $onboarding->save();

            DB::commit();

            return to_json([
                'success' => true,
                'message' => 'Final submission completed successfully.',
                'next_step' => 9,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'success' => false,
                'message' => 'Failed to save final submission.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Digital Signature data (Step 10)
     */
    public function getDigitalSignatureData(Request $request)
    {
        $request->validate([
            'onboardingId' => 'required|string',
        ]);

        try {
            $onboardingNumber = Crypt::decrypt($request->onboardingId);
        } catch (\Exception $e) {
            return to_json([
                'message' => 'Invalid onboarding ID.',
                'error' => 'Invalid onboarding ID.',
            ], 400);
        }

        $onboarding = OnboardingList::where('onboarding_number', $onboardingNumber)->first();
        if (!$onboarding) {
            return to_json([
                'message' => 'Onboarding not found.',
                'error' => 'Not found.',
            ], 404);
        }

        $digitalSignature = DigitalSignature::where('onboarding_list_id', $onboarding->id)->first();

        $employeeFullName = '';
        if ($onboarding) {
            $employeeFullName = trim($onboarding->applicant_first_name . ' ' . $onboarding->applicant_middle_initial . ' ' . $onboarding->applicant_last_name);
        }

        $formData = null;
        if ($digitalSignature) {
            $formData = [
                'digital_lname' => $digitalSignature->signature,
                'digital_date' => $digitalSignature->date,
            ];
        }

        return to_json([
            'success' => true,
            'employee_full_name' => $employeeFullName,
            'status' => $onboarding->status,
            'form' => $formData,
        ]);
    }

    /**
     * Save Digital Signature data (Step 10)
     */
    public function saveDigitalSignature(Request $request)
    {
        $request->validate([
            'onboardingId' => 'required|string',
            'digital_fname' => 'required|string|max:255',
            'digital_lname' => 'required|string|max:255',
            'digital_date' => 'required|string|max:255',
        ]);

        try {
            $onboardingNumber = Crypt::decrypt($request->onboardingId);
        } catch (\Exception $e) {
            return to_json([
                'message' => 'Invalid onboarding ID.',
                'error' => 'Invalid onboarding ID.',
            ], 400);
        }

        $onboarding = OnboardingList::where('onboarding_number', $onboardingNumber)->first();
        if (!$onboarding) {
            return to_json([
                'message' => 'Onboarding record not found.',
                'error' => 'Not found.',
            ], 404);
        }

        try {
            DigitalSignature::updateOrCreate(
                ['onboarding_list_id' => $onboarding->id],
                [
                    'employee_id' => $onboarding->employee_id,
                    'employee_name' => $request->digital_fname,
                    'signature' => $request->digital_lname,
                    'date' => $request->digital_date,
                ]
            );

            // Update onboarding_list with digital signature status
            $onboarding->digital_signature_completed = true;
            $onboarding->digital_signature_date = now();

            $onboarding->status = 'form_submitted';
            // Update process_id to move to next step or completion
            if ($onboarding->process_id == 9) {
                $onboarding->process_id = 10;
            }
            Employee::where('id', $onboarding->employee_id)->update(['onboarding_status' => 'form_submitted']);



            $onboarding->save();

            Employee::where('id', $onboarding->employee_id)->update([
                'onboarding_status' => $onboarding->status,
                'employee_type' => $onboarding->status == 'verified' && $onboarding->employee->employee_rates ? 'Completed' : 'New',
                'first_name' => $onboarding->applicant_first_name,
                'last_name' => $onboarding->applicant_last_name,
                'middle_name' => $onboarding->applicant_middle_initial,
                'dob' => $onboarding->dob,
                'street' => $onboarding->applicant_address,
                'city' => $onboarding->city,
                'state' => $onboarding->state,
                'zip' => $onboarding->zip_code,
                'phone' => $onboarding->applicant_contact_number,
                'email' => $onboarding->applicant_email,
                'document_approved' => true,
            ]);

            if ($this->workbrightService->isI9WithWorkBrightEnabled() && $onboarding->work_bright_employee_id == null) {
                if (empty($onboarding->work_bright_employee_id)) {
                    $employeeCreate = $this->createWorkBrightEmployee($onboarding);
                    if (isset($employeeCreate['message'])) {
                        return to_json([
                            'success' => false,
                            'message' => $employeeCreate['message'],
                            'error' => $employeeCreate['error'],
                        ], 400);
                    }
                    $onboarding->work_bright_employee_id = $employeeCreate['employee_id'];
                    $onboarding->work_bright_response = [
                        'employee_create' => $employeeCreate['response'],
                    ];
                    $onboarding->save();
                }
            }



            if ($onboarding->final_status == 'rejected') {
                $reviewUser = User::find($onboarding->user_id);
                if (!empty($reviewUser?->email)) {
                    $employeeFullName = trim(
                        ($onboarding->applicant_first_name ?? '') . ' ' .
                            ($onboarding->applicant_middle_initial ?? '') . ' ' .
                            ($onboarding->applicant_last_name ?? '')
                    );

                    $html = view('emails.onboarding-resubmitted', [
                        'employeeFullName' => $employeeFullName ?: 'Employee',
                        'onboardingNumber' => $onboarding->onboarding_number,
                    ])->render();

                    MailService::sendMail(
                        $reviewUser->email,
                        'Onboarding form submitted again',
                        $html
                    );
                }
            }

            if (!$onboarding->i9_with_work_bright) {
                $this->storeI9PdfAsEmployeeDocument($onboarding);
                $this->storeW4PdfAsEmployeeDocument($onboarding);
            }

            $this->storeBenefitAcknowledgmentPdfAsEmployeeDocument($onboarding);
            $this->storeBenefitPdfAsEmployeeDocument($onboarding);
            $this->storeHireFormPdfAsEmployeeDocument($onboarding);
            $this->storeDirectDepositPdfAsEmployeeDocument($onboarding);

            return to_json([
                'success' => true,
                'message' => 'Digital signature saved successfully.',
                'next_step' => 10,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save digital signature / WorkBright onboarding', [
                'onboarding_id' => $onboarding->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return to_json([
                'success' => false,
                'message' => 'Failed to save digital signature.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function sendWorkbrightEmail(Request $request)
    {
        $this->authorize('access', 'employee.send-workbright-email');
        $onboarding = OnboardingList::findOrFail($request->onboarding_list_id);

        try {
            if ($this->workbrightService->isI9WithWorkBrightEnabled() && $onboarding->work_bright_employee_id == null) {
                if (empty($onboarding->work_bright_employee_id)) {
                    $employeeCreate = $this->createWorkBrightEmployee($onboarding);
                    if (isset($employeeCreate['message'])) {
                        return to_json([
                            'success' => false,
                            'message' => $employeeCreate['message'],
                            'error' => $employeeCreate['error'],
                        ], 400);
                    }
                    $onboarding->work_bright_employee_id = $employeeCreate['employee_id'];
                    $onboarding->work_bright_response = [
                        'employee_create' => $employeeCreate['response'],
                    ];
                    $onboarding->save();
                }
                return to_json([
                    'success' => true,
                    'message' => 'Workbright email sent successfully'
                ]);
            }
            return to_json([
                'success' => true,
                'message' => 'Workbright email already sent'
            ]);
        } catch (\Exception $e) {

            return to_json([
                'success' => false,
                'message' => 'Failed to send Workbright email',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function workbrightTest(Request $request)
    {
        /** @var \Illuminate\Http\Client\Response $response */
        $response = $this->workbrightService->getEmployeeById(756);

        $array = [];
        $submissions = $this->workbrightService->getAllSubmissions($response['id']);
        $array[] = [
            'employee_id' => $response['id'],
            'submissions' => $submissions,
        ];

        return to_json([
            'success' => true,
            'employee_groups' => $array,
            'response' => $response,
        ]);
    }

    protected function storeI9PdfAsEmployeeDocument(OnboardingList $onboarding): void
    {
        $formI9 = FormI9::where('onboarding_list_id', $onboarding->id)
            ->with(['preparerTranslators', 'reverifications'])
            ->first();

        if (! $formI9 || ! $onboarding->employee_id) {
            return;
        }

        $onboarding->load(['company', 'employee']);

        $pdfOptions = [
            'isRemoteEnabled' => true,
            'isFontSubsettingEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'chroot' => public_path(),
        ];

        $pdf = Pdf::loadView('pdfs.i9-pdf', [
            'onboarding' => $onboarding,
            'formI9' => $formI9,
        ])->setOptions($pdfOptions);

        $filename = 'I9-Form-' . ($onboarding->onboarding_number ?? $onboarding->id) . '-' . now()->format('Y-m-d') . '.pdf';
        $directory = 'employee-documents/' . $onboarding->employee_id;

        $tmpPath = tempnam(sys_get_temp_dir(), 'i9-pdf');
        file_put_contents($tmpPath, $pdf->output());
        $uploadedFile = new UploadedFile($tmpPath, $filename, 'application/pdf', null, true);

        $fileUploadService = app(FileUploadService::class);
        $result = $fileUploadService->store($uploadedFile, $directory);
        $path = $result['path'];
        @unlink($tmpPath);

        $existing = EmployeeDocument::where('employee_id', $onboarding->employee_id)
            ->where('document_name', 'like', '%I-9%')
            ->first();

        if ($existing) {
            $oldPath = $existing->document_path;
            if (! empty($oldPath) && ! str_starts_with($oldPath, 'http')) {
                $fileUploadService->delete($oldPath);
            }
            $existing->update([
                'document_path' => $path,
                'document_name' => 'I-9 Form',
            ]);
        } else {
            EmployeeDocument::create([
                'employee_id' => $onboarding->employee_id,
                'document_name' => 'I-9 Form',
                'document_path' => $path,
            ]);
        }
    }

    /**
     * Generate W-4 PDF and store it in the employee documents table.
     */
    protected function storeW4PdfAsEmployeeDocument(OnboardingList $onboarding): void
    {
        $formW4 = FormW4::where('onboarding_list_id', $onboarding->id)->first();

        if (! $formW4 || ! $onboarding->employee_id) {
            return;
        }

        $onboarding->load(['company', 'employee']);

        $pdfOptions = [
            'isRemoteEnabled' => true,
            'isFontSubsettingEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'chroot' => public_path(),
        ];

        $pdf = Pdf::loadView('pdfs.w4-pdf', [
            'onboarding' => $onboarding,
            'formW4' => $formW4,
        ])->setOptions($pdfOptions);

        $filename = 'W4-Form-' . ($onboarding->onboarding_number ?? $onboarding->id) . '-' . now()->format('Y-m-d') . '.pdf';
        $directory = 'employee-documents/' . $onboarding->employee_id;

        $tmpPath = tempnam(sys_get_temp_dir(), 'w4-pdf');
        file_put_contents($tmpPath, $pdf->output());
        $uploadedFile = new UploadedFile($tmpPath, $filename, 'application/pdf', null, true);

        $fileUploadService = app(FileUploadService::class);
        $result = $fileUploadService->store($uploadedFile, $directory);
        $path = $result['path'];
        @unlink($tmpPath);

        $existing = EmployeeDocument::where('employee_id', $onboarding->employee_id)
            ->where('document_name', 'like', '%W-4%')
            ->first();

        if ($existing) {
            $oldPath = $existing->document_path;
            if (! empty($oldPath) && ! str_starts_with($oldPath, 'http')) {
                $fileUploadService->delete($oldPath);
            }
            $existing->update([
                'document_path' => $path,
                'document_name' => 'W-4 Form',
            ]);
        } else {
            EmployeeDocument::create([
                'employee_id' => $onboarding->employee_id,
                'document_name' => 'W-4 Form',
                'document_path' => $path,
            ]);
        }
    }

    protected function resolveBenefitPlanYear(): string
    {
        $plan = BenefitElection::activePlan();

        return $plan['plan_year'] ?? '2026-2027';
    }

    /**
     * Generate Benefits Acknowledgement PDF and store it in the employee documents table.
     */
    protected function storeBenefitAcknowledgmentPdfAsEmployeeDocument($onboarding, $employee = null): void
    {
        try {

            $employeeId = null;
            if ($onboarding) {
                $benefitEnrollment = BenefitEnrollment::where('onboarding_list_id', $onboarding->id)->first();
                $employeeId = $onboarding->employee_id;
            } else {
                $benefitEnrollment = BenefitEnrollment::where('employee_id', $employee->id)->first();
                $employeeId = $employee->id;
            }

            $employee = Employee::find($employeeId);
            $state = BenefitElection::resolveBenefitStateCode($onboarding, $employee);
            $planYear = $this->resolveBenefitPlanYear();

            if (! $benefitEnrollment || ! $employeeId || ! $employee) {
                return;
            }

            if ($onboarding) {
                $onboarding->load(['company', 'employee']);
            }

            $pdfOptions = [
                'isRemoteEnabled' => true,
                'isFontSubsettingEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'chroot' => public_path(),
            ];

            $pdf = Pdf::loadView('pdfs.benefit-acknowledgment', [
                'onboarding' => $onboarding,
                'employee' => $employee,
                'benefitEnrollment' => $benefitEnrollment,
                'state' => $state,
                'planYear' => $planYear,
            ])->setOptions($pdfOptions);

            $identifier = $onboarding->onboarding_number ?? $employee->benefit_code ?? $employeeId;
            $filename = 'Benefit-Acknowledgment-' . $identifier . '-' . $planYear . '.pdf';
            $directory = 'employee-documents/' . $employeeId;
            $documentName = 'Benefit Acknowledgment ' . $state . ' ' . $planYear;

            $tmpPath = tempnam(sys_get_temp_dir(), 'benefit-ack-pdf');
            file_put_contents($tmpPath, $pdf->output());
            $uploadedFile = new UploadedFile($tmpPath, $filename, 'application/pdf', null, true);

            $fileUploadService = app(FileUploadService::class);
            $result = $fileUploadService->store($uploadedFile, $directory);
            $path = $result['path'];
            @unlink($tmpPath);

            $existing = EmployeeDocument::where('employee_id', $employeeId)
                ->where('document_name', 'like', '%Benefit Acknowledgment%')
                ->where('document_name', 'like', '%' . $planYear . '%')
                ->where('document_name', 'like', '%' . $state . '%')
                ->first();

            if ($existing) {
                $oldPath = $existing->document_path;
                if (! empty($oldPath) && ! str_starts_with($oldPath, 'http')) {
                    $fileUploadService->delete($oldPath);
                }
                $existing->update([
                    'document_path' => $path,
                    'document_name' => $documentName,
                ]);
            } else {
                EmployeeDocument::create([
                    'employee_id' => $employeeId,
                    'document_name' => $documentName,
                    'document_path' => $path,
                ]);
            }
        } catch (\Throwable $th) {
            info($th);
        }
    }

    /**
     * Generate Benefit Enrollment PDF and store it in the employee documents table.
     */
    protected function storeBenefitPdfAsEmployeeDocument($onboarding, $employee = null, $state = null): void
    {
        $employee_id = null;
        if ($onboarding) {

            $benefitEnrollment = BenefitEnrollment::where('onboarding_list_id', $onboarding->id)
                ->with('dependents')
                ->first();
            $employee_id = $onboarding->employee_id;
        } else {
            $benefitEnrollment = BenefitEnrollment::where('employee_id', $employee->id)
                ->with('dependents')
                ->first();
            $employee_id = $employee->id;
        }
        $employee = Employee::find($employee_id);
        $state = BenefitElection::resolveBenefitStateCode($onboarding, $employee);
        $planYear = $this->resolveBenefitPlanYear();

        if (! $benefitEnrollment || ($onboarding && ! $onboarding->employee_id) || ($employee && ! $employee->id)) {
            return;
        }
        if ($onboarding) {
            $onboarding->load(['company', 'employee', 'benefitEnrollment.dependents']);
        } else {
            $employee->load(['employeeRates', 'benefitEnrollment.dependents']);
        }
        $employeeId = $onboarding ? $onboarding->employee_id : $employee->id;

        if ($benefitEnrollment->benefit_acknowledgment === 'decline') {

            $existing = EmployeeDocument::where('employee_id', $employeeId)
                ->where('document_name', 'like', '%Benefit Enrollment%')
                ->where('document_name', 'like', '%' . $planYear . '%')
                ->where('document_name', 'like', '%' . $state . '%')
                ->delete();


            return;
        }


        $pdfOptions = [
            'isRemoteEnabled' => true,
            'isFontSubsettingEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'chroot' => public_path(),
        ];

        $pdf = Pdf::loadView('pdfs.benefit', [
            'onboarding' => $onboarding,
            'employee' => $employee,
            'benefitEnrollment' => $benefitEnrollment,
            'state' => $state,
        ])->setOptions($pdfOptions);

        $filename = 'Benefit-Enrollment-' . ($onboarding->onboarding_number ?? $employee->benefit_code) . '-' . $planYear . '.pdf';
        $directory = 'employee-documents/' . $employeeId;

        $tmpPath = tempnam(sys_get_temp_dir(), 'benefit-pdf');
        file_put_contents($tmpPath, $pdf->output());
        $uploadedFile = new UploadedFile($tmpPath, $filename, 'application/pdf', null, true);

        $fileUploadService = app(FileUploadService::class);
        $result = $fileUploadService->store($uploadedFile, $directory);
        $path = $result['path'];
        @unlink($tmpPath);

        $existing = EmployeeDocument::where('employee_id', $employeeId)
            ->where('document_name', 'like', '%Benefit Enrollment%')
            ->where('document_name', 'like', '%' . $planYear . '%')
            ->where('document_name', 'like', '%' . $state . '%')
            ->first();

        if ($existing) {
            $oldPath = $existing->document_path;
            if (! empty($oldPath) && ! str_starts_with($oldPath, 'http')) {
                $fileUploadService->delete($oldPath);
            }
            $existing->update([
                'document_path' => $path,
                'document_name' => 'Benefit Enrollment ' . $state . ' ' . $planYear,
            ]);
        } else {
            EmployeeDocument::create([
                'employee_id' => $employeeId,
                'document_name' => 'Benefit Enrollment ' . $state . ' ' . $planYear,
                'document_path' => $path,
            ]);
        }
    }

    /**
     * Generate Hire Form PDF and store it in the employee documents table.
     */
    protected function storeHireFormPdfAsEmployeeDocument(OnboardingList $onboarding): void
    {
        if (! $onboarding->employee_id) {
            return;
        }

        $onboarding->load(['company', 'employee']);
        $emergencyContact = EmergencyContact::where('onboarding_list_id', $onboarding->id)->first();

        $pdfOptions = [
            'isRemoteEnabled' => true,
            'isFontSubsettingEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'chroot' => public_path(),
        ];

        $pdf = Pdf::loadView('pdfs.hire-form-pdf', [
            'onboarding' => $onboarding,
            'emergencyContact' => $emergencyContact,
        ])->setOptions($pdfOptions);

        $filename = 'Hire-Form-' . ($onboarding->onboarding_number ?? $onboarding->id) . '-' . now()->format('Y-m-d') . '.pdf';
        $directory = 'employee-documents/' . $onboarding->employee_id;

        $tmpPath = tempnam(sys_get_temp_dir(), 'hire-form-pdf');
        file_put_contents($tmpPath, $pdf->output());
        $uploadedFile = new UploadedFile($tmpPath, $filename, 'application/pdf', null, true);

        $fileUploadService = app(FileUploadService::class);
        $result = $fileUploadService->store($uploadedFile, $directory);
        $path = $result['path'];
        @unlink($tmpPath);

        $existing = EmployeeDocument::where('employee_id', $onboarding->employee_id)
            ->where('document_name', 'like', '%Hire Form%')
            ->first();
        if ($existing) {
            $oldPath = $existing->document_path;
            if (! empty($oldPath) && ! str_starts_with($oldPath, 'http')) {
                $fileUploadService->delete($oldPath);
            }
            $existing->update([
                'document_path' => $path,
                'document_name' => 'Hire Form',
            ]);
        } else {
            EmployeeDocument::create([
                'employee_id' => $onboarding->employee_id,
                'document_name' => 'Hire Form',
                'document_path' => $path,
            ]);
        }
    }

    /**
     * Generate Direct Deposit PDF and store it in the employee documents table.
     */
    protected function storeDirectDepositPdfAsEmployeeDocument(OnboardingList $onboarding): void
    {
        $directDepositData = DirectDeposit::where('onboarding_list_id', $onboarding->id)->first();

        if (! $directDepositData || ! $onboarding->employee_id) {
            return;
        }

        $onboarding->load(['company', 'employee']);

        $employeeProfile = (object) [
            'cp_firstName' => $onboarding->applicant_first_name ?? '',
            'cp_middleInitial' => $onboarding->applicant_middle_initial ?? '',
            'cp_lastName' => $onboarding->applicant_last_name ?? '',
            'cp_posId' => $onboarding->employee->pos_id ?? $onboarding->employee_id ?? $onboarding->onboarding_number ?? '',
        ];

        $storeData = (object) [
            'company_name' => $onboarding->company->name ?? '',
        ];

        $pdfOptions = [
            'isRemoteEnabled' => true,
            'isFontSubsettingEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'chroot' => public_path(),
        ];

        $pdf = Pdf::loadView('pdfs.direct-deposit', [
            'directDepositData' => $directDepositData,
            'storeData' => $storeData,
            'company' => $onboarding->company,
            'employeeProfile' => $employeeProfile,
        ])->setOptions($pdfOptions);

        $filename = 'Direct-Deposit-' . ($onboarding->onboarding_number ?? $onboarding->id) . '-' . now()->format('Y-m-d') . '.pdf';
        $directory = 'employee-documents/' . $onboarding->employee_id;

        $tmpPath = tempnam(sys_get_temp_dir(), 'direct-deposit-pdf');
        file_put_contents($tmpPath, $pdf->output());
        $uploadedFile = new UploadedFile($tmpPath, $filename, 'application/pdf', null, true);

        $fileUploadService = app(FileUploadService::class);
        $result = $fileUploadService->store($uploadedFile, $directory);
        $path = $result['path'];
        @unlink($tmpPath);

        $existing = EmployeeDocument::where('employee_id', $onboarding->employee_id)
            ->where('document_name', 'like', '%Direct Deposit%')
            ->first();

        if ($existing) {
            $oldPath = $existing->document_path;
            if (! empty($oldPath) && ! str_starts_with($oldPath, 'http')) {
                $fileUploadService->delete($oldPath);
            }
            $existing->update([
                'document_path' => $path,
                'document_name' => 'Direct Deposit',
            ]);
        } else {
            EmployeeDocument::create([
                'employee_id' => $onboarding->employee_id,
                'document_name' => 'Direct Deposit',
                'document_path' => $path,
            ]);
        }
    }

    public function getCurrentYearBenefitEnrollmentFile(Request $request)
    {
        $name = 'Benefit Enrollment';
        try {
            $employee = Employee::findOrFail($request->employee_id);
            $benefitEnrollmentpdf = EmployeeDocument::where('employee_id', $employee->id)
                ->where('document_name', 'like', '%' . $name . '%')
                ->orderBy('created_at', 'desc')
                ->first();
            if (!$benefitEnrollmentpdf) {
                return response()->json([
                    'success' => false,
                    'message' => 'Benefit enrollment PDF not found'
                ], 404);
            }
            return response()->json([
                'success' => true,
                'message' => 'Benefit enrollment PDF found',
                'url' => $benefitEnrollmentpdf->document_path_url
            ]);
        } catch (\Throwable $th) {
            info($th);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $th->getMessage()
            ], 500);
        }
    }

    public function regenerateOnboardingEmployeeDocuments(OnboardingList $onboarding): void
    {
        $this->storeBenefitAcknowledgmentPdfAsEmployeeDocument($onboarding);
        $this->storeBenefitPdfAsEmployeeDocument($onboarding);
        $this->storeHireFormPdfAsEmployeeDocument($onboarding);
        $this->storeDirectDepositPdfAsEmployeeDocument($onboarding);
    }
}
