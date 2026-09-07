<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Onboarding\DirectDeposit;
use App\Models\Onboarding\EmployeeDocument;
use App\Models\Onboarding\EmergencyContact;
use App\Models\Onboarding\FinalSubmission;
use App\Models\Onboarding\BenefitEnrollment;
use App\Models\Onboarding\FormI9;
use App\Models\Onboarding\FormW4;
use App\Models\Onboarding\OnboardingHandbook;
use App\Models\Onboarding\OnboardingList;
use App\Models\Onboarding\OnboardingLogs;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\FileUploadService;
use App\Services\MailService;
use App\Support\BenefitElection;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class I9ReviewController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('access', 'i9-review.index');

        $collection = OnboardingList::with(['company', 'employee'])
        ->whereDoesntHave('employee', function ($query) {
            $query->where('rejected', true);
        })
        ->where(function ($q) {
            $q->where('status', '!=', 'verified')
            ->where('process_id', '=', 10);
        })
        ->authorizedCompanies('company_id')
        ->filter();

        return to_json([
            'collection' => $collection,
        ]);
    }

    public function show($id)
    {
        $this->authorize('access', 'i9-review.index');

        $onboarding = OnboardingList::with(['company', 'employee', 'employee.workgroup', 'employee.employeeRatesRequests'])
            ->findOrFail($id);

        $handbookData = OnboardingHandbook::where('onboarding_id', $onboarding->id)->first();
        if($handbookData){
            $handbookData->company_code = $onboarding->company->store_number;
        }
        $i9Data = FormI9::where('onboarding_list_id', $onboarding->id)->with('preparerTranslators', 'reverifications')->first();

        $fileUploadService = app(FileUploadService::class);
        if($i9Data){
            $i9Data->list_a_file_path = isset($i9Data->list_a_file_path) ? $fileUploadService->url($i9Data->list_a_file_path) : null;
            $i9Data->list_b_file_path = isset($i9Data->list_b_file_path) ? $fileUploadService->url($i9Data->list_b_file_path) : null;
            $i9Data->list_c_file_path = isset($i9Data->list_c_file_path) ? $fileUploadService->url($i9Data->list_c_file_path) : null;
            $i9Data->additional_document_path = isset($i9Data->additional_document_path) ? $fileUploadService->url($i9Data->additional_document_path) : null;
        }

        $w4Data = FormW4::where('onboarding_list_id', $onboarding->id)->first();
        $directDepositData = DirectDeposit::where('onboarding_list_id', $onboarding->id)->first();
        $emergencyContactData = EmergencyContact::where('onboarding_list_id', $onboarding->id)->first();
        $finalAcknowledgmentData = FinalSubmission::where('onboarding_list_id', $onboarding->id)->first();

        $verifiedI9 = null;
        if (!empty($onboarding->verified_i9) && is_string($onboarding->verified_i9)) {
            $verifiedI9 = json_decode($onboarding->verified_i9);
        }

        $verifiedW4 = null;
        if (!empty($onboarding->verified_w4) && is_string($onboarding->verified_w4)) {
            $verifiedW4 = json_decode($onboarding->verified_w4);
        }

        $data = [
            'handbook' => $handbookData,
            'form_i9' => $i9Data,
            'verified_i9' => $verifiedI9,
            'form_w4' => $w4Data,
            'verified_w4' => $verifiedW4,
            'direct_deposit' => $directDepositData,
            'emergency_contact' => $emergencyContactData,
            'final_acknowledgment' => $finalAcknowledgmentData,
        ];

        return to_json([
            'model' => array_merge($onboarding->toArray(), $data),
        ]);
    }

    public function approve(Request $request, $id)
    {
        $this->authorize('access', 'i9-review.index');

        DB::beginTransaction();
        try {
            $onboarding = OnboardingList::findOrFail($id);

            if($onboarding->onboarding_status == 'employee_athorized'){
                $onboarding->status = 'verified';
                $onboarding->final_status = 'approved';

            }else{
                $onboarding->status = 'document_approved';
            }
            $onboarding->document_approved = true;
            $onboarding->document_approved_at = now();
            
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
            
            if(!$onboarding->i9_with_work_bright){
                $verifiedI9 = json_decode($onboarding->verified_i9, true) ?? [];
                $verifiedI9['onboardStatus'] = 'verified';
                $verifiedI9['verified_by'] = Auth::id();
                $verifiedI9['verified_at'] = now()->toDateTimeString();
                $onboarding->verified_i9 = json_encode($verifiedI9);
            }

            $onboarding->save();
            if(!$onboarding->i9_with_work_bright){
                $this->storeI9PdfAsEmployeeDocument($onboarding);
                $this->storeW4PdfAsEmployeeDocument($onboarding);
            }

            $this->storeBenefitAcknowledgmentPdfAsEmployeeDocument($onboarding);
            $this->storeBenefitPdfAsEmployeeDocument($onboarding);
            $this->storeHireFormPdfAsEmployeeDocument($onboarding);
            $this->storeDirectDepositPdfAsEmployeeDocument($onboarding);

            ActivityLogService::logApprove(
                'onboarding_list',
                $onboarding->id,
                null,
                ['verified_i9' => !$onboarding->i9_with_work_bright ? $verifiedI9 : null],
                'I-9 form approved'
            );

            DB::commit();
            return to_json([
                'success' => true,
                'message' => 'I-9 form approved successfully',
            ]);
        } catch (\Exception $e) {
            dd($e);
            DB::rollBack();
            return to_json([
                'success' => false,
                'message' => 'Failed to approve I-9 form',
            ], 500);
        }
    }

    public function reject(Request $request, $id)
    {
        $this->authorize('access', 'i9-review.index');

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $onboarding = OnboardingList::with('employee')->findOrFail($id);

            $verifiedI9 = json_decode($onboarding->verified_i9, true) ?? [];
            $verifiedI9['onboardStatus'] = 'in_complete_form';
            $verifiedI9['onboardRemark'] = $request->reason;
            $verifiedI9['rejected_by'] = Auth::id();
            $verifiedI9['rejected_at'] = now()->toDateTimeString();

            $onboarding->status = 'in_complete_form';
            $onboarding->final_status = 'rejected';
            $onboarding->user_id = Auth::id();
            $onboarding->process_id = 1;
            $onboarding->document_approved = false;
            $onboarding->document_approved_at = null;
            $onboarding->verified_i9 = json_encode($verifiedI9);
            $onboarding->save();

            Employee::where('id', $onboarding->employee_id)->update([
                'onboarding_status' => 'in_complete_form',
                'document_approved' => false,
            ]);

            if (!empty($onboarding->applicant_email)) {
                $employeeName = trim(($onboarding->applicant_first_name ?? '') . ' ' . ($onboarding->applicant_last_name ?? ''));
                $html = view('emails.onboarding-note', [
                    'employeeName' => $employeeName ?: 'Employee',
                    'note' => $request->reason,
                    'onboardingNumber' => $onboarding->onboarding_number,
                ])->render();

                $user = User::find($onboarding->employee->updated_by);
                $cc = $user ? [['email'=>$user->email]] : [];
                
                MailService::sendMail(
                    $onboarding->applicant_email,
                    'Onboarding form note from HR',
                    $html,
                    [],
                    $cc
                );
            }

            ActivityLogService::logReview(
                'onboarding_list',
                $onboarding->id,
                null,
                ['verified_i9' => $verifiedI9, 'reason' => $request->reason],
                'I-9 form rejected'
            );

            DB::commit();
            return to_json([
                'success' => true,
                'message' => 'Note sent and onboarding marked as incomplete',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'success' => false,
                'message' => 'Failed to reject I-9 form',
            ], 500);
        }
    }

    public function approveW4(Request $request, $id)
    {
        $this->authorize('access', 'i9-review.index');

        DB::beginTransaction();
        try {
            $onboarding = OnboardingList::findOrFail($id);

            $verifiedW4 = json_decode($onboarding->verified_w4, true) ?? [];
            $verifiedW4['w4Status'] = 'verified';
            $verifiedW4['verified_by'] = Auth::id();
            $verifiedW4['verified_at'] = now()->toDateTimeString();

            $onboarding->status = 'form_submitted';

            $onboarding->verified_w4 = json_encode($verifiedW4);
            $onboarding->save();


            ActivityLogService::logApprove(
                'onboarding_list',
                $onboarding->id,
                null,
                ['verified_w4' => $verifiedW4],
                'W-4 form approved'
            );

            DB::commit();
            return to_json([
                'success' => true,
                'message' => 'W-4 form approved successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'success' => false,
                'message' => 'Failed to approve W-4 form',
            ], 500);
        }
    }

    public function rejectW4(Request $request, $id)
    {
        $this->authorize('access', 'i9-review.index');

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $onboarding = OnboardingList::findOrFail($id);

            $verifiedW4 = json_decode($onboarding->verified_w4, true) ?? [];
            $verifiedW4['w4Status'] = 'rejected';
            $verifiedW4['w4Remark'] = $request->reason;
            $verifiedW4['rejected_by'] = Auth::id();
            $verifiedW4['rejected_at'] = now()->toDateTimeString();

            $onboarding->status = 'form_submitted';

            $onboarding->verified_w4 = json_encode($verifiedW4);
            $onboarding->save();

            ActivityLogService::logReview(
                'onboarding_list',
                $onboarding->id,
                null,
                ['verified_w4' => $verifiedW4, 'reason' => $request->reason],
                'W-4 form rejected'
            );

            DB::commit();
            return to_json([
                'success' => true,
                'message' => 'W-4 form sent back for corrections',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'success' => false,
                'message' => 'Failed to reject W-4 form',
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $this->authorize('access', 'i9-review.index');

        $request->validate([
            'action' => 'required|string|in:i9_approve,w4_approve,i9_w4_approve,i9_reject,w4_reject,i9_w4_reject,verified',
            'reason' => 'nullable|string|max:500',
        ]);

        $action = $request->input('action');
        $reason = trim((string) $request->input('reason', ''));
        $isRejectAction = in_array($action, ['i9_reject', 'w4_reject', 'i9_w4_reject'], true);

        if ($isRejectAction && $reason === '') {
            return to_json([
                'success' => false,
                'message' => 'Reject reason is required.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $onboarding = OnboardingList::findOrFail($id);
            $previousStatus = $onboarding->status;
            $description = '';
            $employeeData=[];

            switch ($action) {
                case 'i9_approve':
                    $onboarding->i9_approved = true;
                    $onboarding->i9_approved_datetime = now();
                    $onboarding->i9_rejected = false;
                    $onboarding->i9_rejected_reason = null;
                    $onboarding->i9_rejected_datetime = null;
                    $description = 'I-9 form approved manually';

                    if($onboarding->w4_approved && $onboarding->document_approved){
                        $onboarding->status = 'waiting_for_section_2_verification';
                        $onboarding->final_status = 'pending';

                    }
                    break;

                case 'w4_approve':
                    $onboarding->w4_approved = true;
                    $onboarding->w4_approved_datetime = now();
                    $onboarding->w4_rejected = false;
                    $onboarding->w4_rejected_reason = null;
                    $onboarding->w4_rejected_datetime = null;
                    $onboarding->final_status = 'pending';

                    $description = 'W-4 form approved manually';
                    if($onboarding->i9_approved && $onboarding->document_approved){
                        $onboarding->status = 'waiting_for_section_2_verification';
                        if($onboarding->employee->employeeRates->count() > 0){
                            $employeeData['employee_type'] = 'Completed';
                        }else{
                            $employeeData['employee_type'] = 'New';
                        }
                    }
                    break;

                case 'i9_w4_approve':
                    $onboarding->i9_approved = true;
                    $onboarding->i9_approved_datetime = now();
                    $onboarding->i9_rejected = false;
                    $onboarding->i9_rejected_reason = null;
                    $onboarding->i9_rejected_datetime = null;
                    $onboarding->final_status = 'pending';

                    $onboarding->w4_approved = true;
                    $onboarding->w4_approved_datetime = now();
                    $onboarding->w4_rejected = false;
                    $onboarding->w4_rejected_reason = null;
                    $onboarding->w4_rejected_datetime = null;
                    $description = 'I-9 and W-4 form approved manually';

                    if( $onboarding->document_approved){
                        $onboarding->status = 'waiting_for_section_2_verification';
                    }
                    break;

                case 'i9_reject':
                    $onboarding->i9_approved = false;
                    $onboarding->i9_approved_datetime = null;
                    $onboarding->i9_rejected = true;
                    $onboarding->i9_rejected_reason = $reason;
                    $onboarding->i9_rejected_datetime = now();
                    $description = 'I-9 form rejected manually';
                    break;

                case 'w4_reject':
                    $onboarding->w4_approved = false;
                    $onboarding->w4_approved_datetime = null;
                    $onboarding->w4_rejected = true;
                    $onboarding->w4_rejected_reason = $reason;
                    $onboarding->w4_rejected_datetime = now();
                    $description = 'W-4 form rejected manually';
                    break;

                case 'i9_w4_reject':
                    $onboarding->i9_approved = false;
                    $onboarding->i9_approved_datetime = null;
                    $onboarding->i9_rejected = true;
                    $onboarding->i9_rejected_reason = $reason;
                    $onboarding->i9_rejected_datetime = now();

                    $onboarding->w4_approved = false;
                    $onboarding->w4_approved_datetime = null;
                    $onboarding->w4_rejected = true;
                    $onboarding->w4_rejected_reason = $reason;
                    $onboarding->w4_rejected_datetime = now();
                    $description = 'I-9 and W-4 form rejected manually';
                    break;

                case 'verified':
                    $onboarding->status = 'verified';
                    $onboarding->final_status = 'approved';
                    $onboarding->document_approved = true;
                    $onboarding->document_approved_at = now();
                   
                    if($onboarding->employee->employeeRates->count() > 0){
                        $employeeData['employee_type'] = 'Completed';
                    }else{
                        $employeeData['employee_type'] = 'Rate Approval';
                    }

                    
                    $description = 'Onboarding verified manually';
                    break;
            }
            if($onboarding->status === 'verified'){
                Employee::where('id', $onboarding->employee_id)->update([
                    'onboarding_status' => $onboarding->status,
                    'employee_type' => $employeeData['employee_type'],
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

                $this->storeI9PdfAsEmployeeDocument($onboarding);
                $this->storeW4PdfAsEmployeeDocument($onboarding);
                $this->storeBenefitAcknowledgmentPdfAsEmployeeDocument($onboarding);
                $this->storeBenefitPdfAsEmployeeDocument($onboarding);
                $this->storeHireFormPdfAsEmployeeDocument($onboarding);
                $this->storeDirectDepositPdfAsEmployeeDocument($onboarding);
            }else if ($onboarding->status !== 'verified') {
                if ($onboarding->i9_approved && $onboarding->w4_approved) {
                    $onboarding->status = 'i9_submitted';
                } else {
                    $onboarding->status = 'form_submitted';
                }
            }

            $onboarding->save();

            Employee::where('id', $onboarding->employee_id)->update([
                'onboarding_status' => $onboarding->status,
                'document_approved' => $onboarding->document_approved,
            ]);

            $logTime = now();
            $statusLog = OnboardingLogs::create([
                'onboarding_list_id' => $onboarding->id,
                'event' => 'status_updated',
                'data' => json_encode([
                    'action' => $action,
                    'previous_status' => $previousStatus,
                    'updated_status' => $onboarding->status,
                    'reason' => $reason ?: null,
                    'updated_by' => Auth::id(),
                ]),
                'description' => $description,
                'date' => $logTime,
            ]);
            $statusLog->created_at = $logTime;
            $statusLog->save();

            DB::commit();

            return to_json([
                'success' => true,
                'message' => 'Status updated successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'success' => false,
                'message' => 'Failed to update status.',
            ], 500);
        }
    }

    public function exportPdf($id)
    {
        $this->authorize('access', 'i9-review.index');

        $onboarding = OnboardingList::with(['company', 'employee'])
            ->findOrFail($id);

        $formI9 = FormI9::where('onboarding_list_id', $onboarding->id)
            ->with(['preparerTranslators', 'reverifications'])
            ->first();

        if (! $formI9) {
            return to_json([
                'success' => false,
                'message' => 'I-9 form not found for this onboarding',
            ], 404);
        }

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

        return $pdf->stream($filename);
    }
    public function exportW4Pdf($id)
    {
        $this->authorize('access', 'i9-review.index');

        $onboarding = OnboardingList::with(['company', 'employee'])
            ->findOrFail($id);

        $formW4 = FormW4::where('onboarding_list_id', $onboarding->id)->first();

        if (! $formW4) {
            abort(404, 'W-4 form not found');
        }

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

        return $pdf->stream($filename);
    }

    public function exportBenefitPdf($id)
    {
        $this->authorize('access', 'i9-review.index');

        $onboarding = OnboardingList::with(['company', 'employee', 'benefitEnrollment.dependents'])
            ->findOrFail($id);

        $benefitEnrollment = BenefitEnrollment::where('onboarding_list_id', $onboarding->id)
            ->with('dependents')
            ->first();

        if (! $benefitEnrollment) {
            abort(404, 'Benefit enrollment form not found');
        }

        if ($benefitEnrollment->benefit_acknowledgment === 'decline') {
            abort(404, 'Benefit enrollment form not found');
        }

        $pdfOptions = [
            'isRemoteEnabled' => true,
            'isFontSubsettingEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'chroot' => public_path(),
        ];

        $employee = $onboarding->employee;
        $state = BenefitElection::resolveBenefitStateCode($onboarding, $employee);

        $pdf = Pdf::loadView('pdfs.benefit', [
            'onboarding' => $onboarding,
            'employee' => $employee,
            'benefitEnrollment' => $benefitEnrollment,
            'state' => $state,
        ])->setOptions($pdfOptions);

        $filename = 'Benefit-Enrollment-' . ($onboarding->onboarding_number ?? $onboarding->id) . '-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->stream($filename);
    }

    public function exportHireFormPdf($id)
    {
        $this->authorize('access', 'i9-review.index');

        $onboarding = OnboardingList::with(['company', 'employee'])
            ->findOrFail($id);

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

        return $pdf->stream($filename);
    }

    public function exportDirectDepositPdf($id)
    {
        $this->authorize('access', 'i9-review.index');

        $onboarding = OnboardingList::with(['company', 'employee'])
            ->findOrFail($id);

        $directDepositData = DirectDeposit::where('onboarding_list_id', $onboarding->id)->first();

        if (! $directDepositData) {
            abort(404, 'Direct deposit form not found');
        }

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

        return $pdf->stream($filename);
    }

    /**
     * Generate I-9 PDF and store it in the employee documents table.
     */
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
        $directory = 'employee-documents/' . $onboarding->employee_id ;

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
    protected function storeBenefitAcknowledgmentPdfAsEmployeeDocument(OnboardingList $onboarding): void
    {
        $benefitEnrollment = BenefitEnrollment::where('onboarding_list_id', $onboarding->id)->first();

        if (! $benefitEnrollment || ! $onboarding->employee_id) {
            return;
        }

        $onboarding->load(['company', 'employee']);
        $employee = $onboarding->employee;
        $state = BenefitElection::resolveBenefitStateCode($onboarding, $employee);
        $planYear = $this->resolveBenefitPlanYear();

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

        $filename = 'Benefit-Acknowledgment-' . ($onboarding->onboarding_number ?? $onboarding->id) . '-' . $planYear . '.pdf';
        $directory = 'employee-documents/' . $onboarding->employee_id;
        $documentName = 'Benefit Acknowledgment ' . $state . ' ' . $planYear;

        $tmpPath = tempnam(sys_get_temp_dir(), 'benefit-ack-pdf');
        file_put_contents($tmpPath, $pdf->output());
        $uploadedFile = new UploadedFile($tmpPath, $filename, 'application/pdf', null, true);

        $fileUploadService = app(FileUploadService::class);
        $result = $fileUploadService->store($uploadedFile, $directory);
        $path = $result['path'];
        @unlink($tmpPath);

        $existing = EmployeeDocument::where('employee_id', $onboarding->employee_id)
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
                'employee_id' => $onboarding->employee_id,
                'document_name' => $documentName,
                'document_path' => $path,
            ]);
        }
    }

    /**
     * Generate Benefit Enrollment PDF and store it in the employee documents table.
     */
    protected function storeBenefitPdfAsEmployeeDocument(OnboardingList $onboarding): void
    {
        $benefitEnrollment = BenefitEnrollment::where('onboarding_list_id', $onboarding->id)
            ->with('dependents')
            ->first();

        if (! $benefitEnrollment || ! $onboarding->employee_id) {
            return;
        }

        if ($benefitEnrollment->benefit_acknowledgment === 'decline') {
            return;
        }

        $onboarding->load(['company', 'employee', 'benefitEnrollment.dependents']);
        $employee = $onboarding->employee;
        $state = BenefitElection::resolveBenefitStateCode($onboarding, $employee);
        $planYear = $this->resolveBenefitPlanYear();

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

        $filename = 'Benefit-Enrollment-' . ($onboarding->onboarding_number ?? $onboarding->id) . '-' . $planYear . '.pdf';
        $directory = 'employee-documents/' . $onboarding->employee_id ;

        $tmpPath = tempnam(sys_get_temp_dir(), 'benefit-pdf');
        file_put_contents($tmpPath, $pdf->output());
        $uploadedFile = new UploadedFile($tmpPath, $filename, 'application/pdf', null, true);

        $fileUploadService = app(FileUploadService::class);
        $result = $fileUploadService->store($uploadedFile, $directory);
        $path = $result['path'];
        @unlink($tmpPath);

        $existing = EmployeeDocument::where('employee_id', $onboarding->employee_id)
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
                'employee_id' => $onboarding->employee_id,
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
        $directory = 'employee-documents/' . $onboarding->employee_id ;

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

    public function updateEmployerCert(Request $request, $id)
    {
        $this->authorize('access', 'i9-review.index');

        $request->validate([
            'first_day_employment' => 'nullable|date',
            'employer_name' => 'nullable|string|max:255',
            'employer_today_date' => 'nullable|date',
            'employer_business_name' => 'nullable|string|max:255',
            'employer_business_address' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $onboarding = OnboardingList::findOrFail($id);
            
            // Update the I-9 form with employer certification data
            $i9Form = FormI9::where('onboarding_list_id', $onboarding->id)->first();
            
            if (!$i9Form) {
                return to_json([
                    'success' => false,
                    'message' => 'I-9 form not found',
                ], 404);
            }

            $i9Form->update([
                'first_day_employment' => $request->first_day_employment,
                'employer_name' => $request->employer_name,
                'employer_today_date' => $request->employer_today_date,
                'employer_business_name' => $request->employer_business_name,
                'employer_business_address' => $request->employer_business_address,
            ]);

            ActivityLogService::logUpdate(
                'i9_forms',
                $i9Form->id,
                $i9Form->getOriginal(),
                $i9Form->toArray(),
                'Employer certification updated'
            );

            DB::commit();
            return to_json([
                'success' => true,
                'message' => 'Employer certification updated successfully',
                'data' => $i9Form,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return to_json([
                'success' => false,
                'message' => 'Failed to update employer certification',
            ], 500);
        }
    }
}
