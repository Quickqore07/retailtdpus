<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Form I-9</title>
    <style>
        @page {
            margin: 10px 20px;
        }

        body {
            margin: 0;
            padding: 0;
            font-size: 10px;
            line-height: 9px;
        }

        .i9-page-2 ol {
            padding-left: 0;
            list-style: none;
        }

        .i9-page-2 li {
            border-bottom: 1px solid;
            padding: 5px;
        }

        .i9-page-2 li:last-child {
            border-bottom: none;
        }

        .i9-field {
            min-height: 12px;
            border-bottom: 1px solid #000;
            margin-bottom: 4px;
        }

        .i9-radio {
            display: inline-block;
            width: 8px;
            height: 8px;
            border: 1px solid #000;
            border-radius: 50%;
            vertical-align: middle;
            margin-right: 2px;
        }

        .i9-radio.filled {
            background: #000;
        }

        .i9-checkbox {
            display: inline-block;
            width: 10px;
            height: 10px;
            border: 1px solid #000;
            background: #fff;
            vertical-align: middle;
            margin-right: 4px;
            text-align: center;
            line-height: 10px;
            font-size: 10px;
            font-family: "DejaVu Sans", sans-serif;
        }

        .i9-checkbox.checked {
            background: #fff;
            font-weight: bold;
        }

        .i9-page-inner {
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        .i9-page-content {
            flex: 1 1 auto;
        }

        .i9-page-footer {
            flex: 0 0 auto;
            font-size: 9px;
            width: 100%;
            border-top: 1px solid #000;
            padding-top: 5px;
            margin-top: 10px;
        }

        .i9-page-footer label:first-child {
            float: left;
        }

        .i9-page-footer label:last-child {
            float: right;
        }
    </style>
</head>
@php
    $employee = $employee ?? $employeeConfirmation?->employee ?? $onboarding?->employee ?? null;

    if (!empty($employeeConfirmation)) {
        $section1 = $employeeConfirmation->section1_employee_data;
        $formI9 = (object) [
            'other_last_names' => '',
            'citizenship_status' => $employeeConfirmation->pdf_citizenship_status,
            'uscis_or_a_number' => $employeeConfirmation->pdf_uscis_or_a_number,
            'alien_authorized_exp_date' => $employeeConfirmation->i9_choice === 'alien'
                ? $employeeConfirmation->work_authorization_exp_date
                : null,
            'uscis_a_number' => $employeeConfirmation->pdf_uscis_or_a_number,
            'form_i94_admission_number' => $employeeConfirmation->pdf_form_i94_admission_number,
            'foreign_passport_number' => $employeeConfirmation->pdf_foreign_passport_number,
            'employee_signature' => ($withoutSignatures ?? false) ? '' : '',
            'section1_today_date' => null,
            'list_a_doc_title_1' => $employeeConfirmation->list_a_doc_type,
            'list_a_issuing_authority_1' => $employeeConfirmation->list_a_issuing_authority,
            'list_a_document_number_1' => $employeeConfirmation->list_a_document_number,
            'list_a_expiration_date_1' => $employeeConfirmation->list_a_expiration_date,
            'list_a_doc_title_2' => null,
            'list_a_issuing_authority_2' => null,
            'list_a_document_number_2' => null,
            'list_a_expiration_date_2' => null,
            'list_a_doc_title_3' => null,
            'list_a_issuing_authority_3' => null,
            'list_a_document_number_3' => null,
            'list_a_expiration_date_3' => null,
            'list_b_doc_title' => $employeeConfirmation->list_b_doc_type,
            'list_b_issuing_authority' => $employeeConfirmation->list_b_issuing_authority,
            'list_b_document_number' => $employeeConfirmation->list_b_document_number,
            'list_b_expiration_date' => $employeeConfirmation->list_b_expiration_date,
            'list_c_doc_title' => $employeeConfirmation->list_c_doc_type,
            'list_c_issuing_authority' => $employeeConfirmation->list_c_issuing_authority,
            'list_c_document_number' => $employeeConfirmation->list_c_document_number,
            'list_c_expiration_date' => $employeeConfirmation->list_c_expiration_date,
            'additional_information' => null,
            'alternative_procedure' => false,
            'first_day_employment' => $employee?->hire_date,
            'employer_name' => null,
            'employer_signature' => ($withoutSignatures ?? false) ? '' : '',
            'employer_today_date' => null,
            'employer_business_name' => $company?->name,
            'employer_business_address' => $company?->address,
        ];
        $formI9->preparerTranslators = collect();
        $formI9->reverifications = collect();
    } else {
        $section1 = $formI9?->section1_employee_data ?? [];
        if (($withoutSignatures ?? false) && $formI9) {
            $formI9->employee_signature = null;
            $formI9->employer_signature = null;
            $formI9->setRelation(
                'preparerTranslators',
                $formI9->preparerTranslators->map(function ($preparer) {
                    $preparer->signature = null;

                    return $preparer;
                })
            );
            $formI9->setRelation(
                'reverifications',
                $formI9->reverifications->map(function ($reverification) {
                    $reverification->employer_signature = null;

                    return $reverification;
                })
            );
        }
    }

    $logoPath = file_exists(public_path('images/i9-logo.png'))
        ? str_replace('\\', '/', realpath(public_path('images/i9-logo.png')))
        : null;
    $documentTypesA = [
        '1' => '1. U.S. Passport or U.S. Passport Card',
        '2' => '2. Permanent Resident Card or Alien Registration Receipt Card (Form I-551)',
        '3' => '3. Foreign passport with temporary I-551 stamp or notation on a machine-readable immigrant visa',
        '4' => '4. Employment Authorization Document (Form I-766) with photograph',
        '5' => '5. For an individual temporarily authorized to work (specific employer due to status/parole):',
        '5a' => 'a. Foreign passport',
        '5b' => 'b. Form I-94 or Form I-94A',
        '5b1' => '(1) Same name as passport',
        '5b2' => '(2) Endorsement of status/parole (valid, not expired, no conflicts)',
        '6' => '6. Passport from FSM or RMI with Form I-94 or I-94A indicating nonimmigrant admission',
    ];
    $documentTypesB = [
        '1' => "1. Driver's license or ID card issued by a State or outlying possession of the U.S.",
        '2' => '2. ID card issued by federal, state, or local government agencies or entities',
        '3' => '3. School ID card with a photograph',
        '4' => "4. Voter's registration card",
        '5' => '5. U.S. Military card or draft record',
        '6' => "6. Military dependent's ID card",
        '7' => '7. U.S. Coast Guard Merchant Mariner Card',
        '8' => '8. Native American tribal document',
        '9' => "9. Driver's license issued by a Canadian government authority",
        '10' => '10. School record or report card',
        '11' => '11. Clinic, doctor, or hospital record',
        '12' => '12. Day-care or nursery school record',
    ];
    $documentTypesC = [
        '1' => '1. A Social Security Account Number card, unless the card includes one of the following restrictions:',
        '1a' => '1(a). NOT VALID FOR EMPLOYMENT',
        '1b' => '1(b). VALID FOR WORK ONLY WITH INS AUTHORIZATION',
        '1c' => '1(c). VALID FOR WORK ONLY WITH DHS AUTHORIZATION',
        '2' => '2. Certification of report of birth issued by the Department of State (Forms DS-1350, FS-545, FS-240)',
        '3' =>
            '3. Original or certified copy of birth certificate issued by a State, county, municipal authority, or territory of the United States bearing an official seal',
        '4' => '4. Native American tribal document',
        '5' => '5. U.S. Citizen ID Card (Form I-197)',
        '6' => '6. Identification Card for Use of Resident Citizen in the United States (Form I-179)',
        '7' => '7. Employment authorization document issued by the Department of Homeland Security',
    ];
    $docA1 = isset($documentTypesA[$formI9?->list_a_doc_title_1])
        ? $documentTypesA[$formI9?->list_a_doc_title_1]
        : $formI9?->list_a_doc_title_1;
    $docB = isset($documentTypesB[$formI9?->list_b_doc_title])
        ? $documentTypesB[$formI9?->list_b_doc_title]
        : $formI9?->list_b_doc_title;
    $docC = isset($documentTypesC[$formI9?->list_c_doc_title])
        ? $documentTypesC[$formI9?->list_c_doc_title]
        : $formI9?->list_c_doc_title;
    $formatDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('m/d/Y') : '';
    $preparer_translators = $formI9?->preparerTranslators?->toArray() ?? [];

    $diff = 4 - count($preparer_translators);
    if ($diff > 0) {
        for ($i = 0; $i < $diff; $i++) {
            $preparer_translators[] = [
                'signature' => ($withoutSignatures ?? false) ? '' : '',
                'signature_date' => '',
                'last_name' => '',
                'first_name' => '',
                'middle_initial' => '',
                'address' => '',
                'city' => '',
                'state' => '',
                'zip_code' => '',
            ];
        }
    }

    $reverifications = $formI9?->reverifications?->toArray() ?? [];
    $diff = 3 - count($reverifications);
    if ($diff > 0) {
        for ($i = 0; $i < $diff; $i++) {
            $reverifications[] = [
                'rehire_date' => '',
                'new_last_name' => '',
                'new_first_name' => '',
                'new_middle_initial' => '',
                'document_title' => '',
                'document_number' => '',
                'expiration_date' => '',
                'employer_representative_name' => '',
                'employer_signature' => ($withoutSignatures ?? false) ? '' : '',
                'today_date' => '',
                'additional_information' => '',
                'alternative_procedure_dhs' => false,
            ];
        }
    }

    if ($withoutSignatures ?? false) {
        foreach ($preparer_translators as &$preparerRow) {
            $preparerRow['signature'] = '';
        }
        unset($preparerRow);

        foreach ($reverifications as &$reverificationRow) {
            $reverificationRow['employer_signature'] = '';
        }
        unset($reverificationRow);
    }
@endphp

<body>
    <div class="inner i9-page-1" style="page-break-after: always;">
        <div class="i9-page-inner">
        <div class="i9-page-content">
        <table style="width: 100%;">
            <tr>
                <td width="20%">
                    @if ($logoPath)
                        <img src="{{ $logoPath }}" style="max-width: 80px; height: auto;">
                    @endif
                </td>
                <td width="60%" style="text-align: center;">
                    <h2 style="margin-top:0;margin-bottom: 5px;font-size: 18px;font-weight: 600;">Employment Eligibility
                        Verification</h2>
                    <h4 style="margin:0;margin-bottom: 5px;font-size: 14px;font-weight: 600;">Department of Homeland
                        Security</h4>
                    <p style="margin:0;font-size: 12px;">U.S. Citizenship and Immigration Services</p>
                </td>
                <td width="20%" style="text-align: center;">
                    <h5 style="margin:0;margin-bottom: 5px;font-size: 14px;font-weight: 600;">USCIS</h5>
                    <h5 style="margin:0;margin-bottom: 5px;font-size: 14px;font-weight: 600;">Form I-9</h5>
                    <p style="margin:0;margin-bottom: 5px;font-size: 12px;">OMB No.1615-0047</p>
                    <p style="margin:0;font-size: 12px;">Expires 07/31/2026</p>
                </td>
            </tr>
        </table>
        <hr style="border-color: #000;border-width: thick;opacity: 1;">
        <div>
            <p style="font-size: 9px;line-height: 9px;"><b>START HERE: Employers must ensure the form instructions are
                    available to employees when completing this form. Employers are liable for failing to comply with
                    the requirements for completing this form. See below and the <a href="https://www.uscis.gov/i-9"
                        target="_blank" style="color: #b1151d;">Instructions.</a></b></p>
            <p style="font-size: 9px;line-height: 9px;"><b>ANTI-DISCRIMINATION NOTICE:</b> All employees can choose
                which acceptable documentation to present for Form I-9. Employers cannot ask employees for documentation
                to verify information in <b>Section 1</b>, or specify which acceptable documentation employees must
                present for <b>Section 2</b> or Supplement B, Reverification and Rehire. Treating employees differently
                based on their citizenship, immigration status, or national origin may be illegal.</p>
        </div>

        <table style="width: 100%;border-collapse: collapse;">
            <tr>
                <td style="border: 1px solid #000;padding: 1px 8px;background-color: #d3d3d3;font-size: 9px;line-height: 9px;"
                    colspan="5"><b>Section 1. Employee Information and Attestation:</b> Employees must complete and
                    sign Section 1 of Form I-9 no later than the <b>first day of employment,</b> but not before
                    accepting a job offer.</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 1px 8px;"><label style="font-size: 9px;">Last Name (Family
                        Name)</label>
                    <div class="i9-field">
                        <p style="margin: 3px 0 0 0;font-size: 9px;">
                            {{ $section1['last_name'] ?? '' }}</p>
                    </div>
                </td>
                <td style="border: 1px solid #000;padding: 1px 8px;"><label style="font-size: 9px;">First Name (Given
                        Name)</label>
                    <div class="i9-field">
                        <p style="margin: 3px 0 0 0;font-size: 9px;">
                            {{ $section1['first_name'] ?? '' }}</p>
                    </div>
                </td>
                <td style="border: 1px solid #000;padding: 1px 8px;"><label style="font-size: 9px;">Middle Initial (if
                        any)</label>
                    <div class="i9-field">
                        <p style="margin: 3px 0 0 0;font-size: 9px;">
                            {{ $section1['middle_initial'] ?? '' }}</p>
                    </div>
                </td>
                <td style="border: 1px solid #000;padding: 1px 8px;" colspan="2"><label style="font-size: 9px;">Other
                        Last Names Used (if any)</label>
                    <div class="i9-field">
                        <p style="margin: 3px 0 0 0;font-size: 9px;">{{ $formI9?->other_last_names ?? '' }}</p>
                    </div>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 1px 8px;" width="28%"><label
                        style="font-size: 9px;">Address (Street Number and Name)</label>
                    <div class="i9-field">
                        <p style="margin: 3px 0 0 0;font-size: 9px;">
                            {{ $section1['address'] ?? '' }}</p>
                    </div>
                </td>
                <td style="border: 1px solid #000;padding: 1px 8px;"><label style="font-size: 9px;">Apt. Number (if
                        any)</label>
                    <div class="i9-field">
                        <p style="margin: 3px 0 0 0;font-size: 9px;">
                            {{ $section1['apt_number'] ?? '' }}</p>
                    </div>
                </td>
                <td style="border: 1px solid #000;padding: 1px 8px;"><label style="font-size: 9px;">City or Town</label>
                    <div class="i9-field">
                        <p style="margin: 3px 0 0 0;font-size: 9px;">{{ $section1['city'] ?? '' }}
                        </p>
                    </div>
                </td>
                <td style="border: 1px solid #000;padding: 1px 8px;"><label style="font-size: 9px;">State</label>
                    <div class="i9-field">
                        <p style="margin: 3px 0 0 0;font-size: 9px;">
                            {{ $section1['state'] ?? '' }}</p>
                    </div>
                </td>
                <td style="border: 1px solid #000;padding: 1px 8px;"><label style="font-size: 9px;">ZIP Code</label>
                    <div class="i9-field">
                        <p style="margin: 3px 0 0 0;font-size: 9px;">
                            {{ $section1['zipcode'] ?? '' }}</p>
                    </div>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 1px 8px;"><label style="font-size: 9px;">Date of Birth
                        (mm/dd/yyyy)</label>
                    <div class="i9-field">
                        <p style="margin: 3px 0 0 0;font-size: 9px;">
                            {{ $formatDate($section1['date_of_birth'] ?? null) }}</p>
                    </div>
                </td>
                <td style="border: 1px solid #000;padding: 1px 8px;" width="23%"><label style="font-size: 9px;">U.S.
                        Social Security Number</label>
                    <div class="i9-field">
                        <p style="margin: 3px 0 0 0;font-size: 9px;">
                            {{ $section1['social_security_number'] ?? '' }}</p>
                    </div>
                </td>
                <td style="border: 1px solid #000;padding: 1px 8px;"><label style="font-size: 9px;">Employee's
                        Email</label>
                    <div class="i9-field">
                        <p style="margin: 3px 0 0 0;font-size: 9px;">
                            {{ $section1['employee_email'] ?? '' }}</p>
                    </div>
                </td>
                <td style="border: 1px solid #000;padding: 1px 8px;" colspan="2"><label
                        style="font-size: 9px;">Employee's Telephone Number</label>
                    <div class="i9-field">
                        <p style="margin: 3px 0 0 0;font-size: 9px;">
                            {{ $section1['employee_telephone'] ?? '' }}</p>
                    </div>
                </td>
            </tr>
        </table>

        <table style="width: 100%;border-collapse: collapse;">
            <tr>
                <td style="border: 1px solid #000;padding: 1px 8px;width: 25%;font-size: 9px;line-height: 9px;"><b>I am
                        aware that federal law provides for imprisonment and/or fines for false statements, or the use
                        of false documents, in connection with the completion of this form. I attest, under penalty of
                        perjury, that this information, including my selection of the box attesting to my citizenship or
                        immigration status, is true and correct.</b></td>
                <td style="border: 1px solid #000;padding: 1px 8px;font-size: 9px;line-height: 9px;">
                    @php $cs = $formI9?->citizenship_status; @endphp
                    <label style="margin-bottom: 8px;display: block;font-size: 9px;"><strong>Check one of the following
                            boxes to attest to your citizenship or immigration status (See page 2 and 3 of the
                            instructions.):</strong></label>
                    <div
                        style="border-bottom: 1px solid #999;padding: 2px 0;padding-bottom:8px; margin-bottom: 9px;font-size: 9px;">
                        <span class="i9-radio @if ($cs == 1) filled @endif"></span> <span>1. A
                            citizen of the United States</span></div>
                    <div
                        style="border-bottom: 1px solid #999;padding: 2px 0;padding-bottom:8px; margin-bottom: 9px;font-size: 9px;">
                        <span class="i9-radio @if ($cs == 2) filled @endif"></span> <span>2. A
                            noncitizen national of the United States (See Instructions.)</span></div>
                    <div
                        style="border-bottom: 1px solid #999;padding: 2px 0;padding-bottom:8px; margin-bottom: 9px;font-size: 9px;">
                        <span class="i9-radio @if ($cs == 3) filled @endif"></span> <span>3. A lawful
                            permanent resident (Enter USCIS or A-Number.)</span>&nbsp;<span
                            style="border-bottom: 1px solid #000;display: inline-block;min-width: 60px;">{{ $formI9?->uscis_or_a_number ?? '' }}</span>
                    </div>
                    <div style="padding: 2px 0;font-size: 9px;"><span
                            class="i9-radio @if ($cs == 4) filled @endif"></span> <span>4. An alien
                            authorized to work until (exp. date, if any)</span><span
                            style="border-bottom: 1px solid #000;display: inline-block;min-width: 60px;">{{ $formatDate($formI9?->alien_authorized_exp_date) }}</span>
                    </div>
                    <label style="display: inline-block;margin-bottom: 5px;font-size: 9px;">If you check Item
                        <strong>Number 4.</strong>, enter one of these:</label>
                    <div style="display: table; width: 100%;margin-top: 5px;">
                        <div style="display: table-row;">
                            <div style="display: table-cell; border: 1px solid #000; padding: 5px;"><label
                                    style="font-size: 9px;">USCIS A-Number</label>
                                <div class="i9-field">
                                    <p style="margin: 3px 0 0 0;font-size: 9px;">{{ $formI9?->uscis_a_number ?? '' }}
                                    </p>
                                </div>
                            </div>
                            <div style="display: table-cell;font-weight: bold;font-size: 12px;text-align: center;">OR
                            </div>
                            <div style="display: table-cell; border: 1px solid #000; padding: 5px;"><label
                                    style="font-size: 9px;">Form I-94 Admission Number</label>
                                <div class="i9-field">
                                    <p style="margin: 3px 0 0 0;font-size: 9px;">
                                        {{ $formI9?->form_i94_admission_number ?? '' }}</p>
                                </div>
                            </div>
                            <div style="display: table-cell;font-weight: bold;font-size: 12px;text-align: center;">OR
                            </div>
                            <div style="display: table-cell; border: 1px solid #000; padding: 5px;"><label
                                    style="font-size: 8px;">Foreign Passport Number and Country of Issuance</label>
                                <div class="i9-field">
                                    <p style="margin: 3px 0 0 0;font-size: 9px;">
                                        {{ $formI9?->foreign_passport_number ?? '' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 1px 8px;"><label style="font-size: 9px;">Signature of
                        Employee</label>
                    <div class="i9-field">
                        <p style="margin: 3px 0 0 0;font-size: 9px;">{{ $formI9?->employee_signature ?? '' }}</p>
                    </div>
                </td>
                <td style="border: 1px solid #000;padding: 1px 8px;"><label style="font-size: 9px;">Today's Date
                        (mm/dd/yyyy)</label>
                    <div class="i9-field">
                        <p style="margin: 3px 0 0 0;font-size: 9px;">{{ $formatDate($formI9?->section1_today_date) }}
                        </p>
                    </div>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 1px 5px;font-size: 8px;font-weight: 500;" colspan="2">If
                    a preparer and/or translator assisted you in completing Section 1, that person MUST complete the <a
                        href="https://www.uscis.gov/i-9" target="_blank" style="color: #b1151d;">Preparer and/or
                        Translator Certification</a> on Page 3.</td>
            </tr>
        </table>

        <table style="width: 100%;border-collapse: collapse;font-size: 9px;">
            <tr>
                <td style="border: 1px solid #000;padding: 1px 8px;background-color: #d3d3d3;"><b>Section 2. Employer
                        Review and Verification:</b> Employers or their authorized representative must complete and sign
                    Section 2 within three business days after the employee's first day of employment, and must
                    physically examine, or examine consistent with an alternative procedure authorized by the Secretary
                    of DHS, documentation from List A OR a combination of documentation from List B and List C. Enter
                    any additional documentation in the Additional Information box; see Instructions.</td>
            </tr>
        </table>

        <table style="width: 100%;border-collapse: collapse;font-size: 9px;">
            <tr>
                <td></td>
                <td style="text-align:center;"><b>List A</b></td>
                <td style="background-color:#d3d3d3;width: 2%;font-size: 12px;"><b>OR</b></td>
                <td style="text-align:center;"><b>List B</b></td>
                <td style="text-align:center;"><b>List C</b></td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 8px 8px;background-color: #d3d3d3;width: 18%;"><b>Document
                        Title 1</b></td>
                <td style="border: 1px solid #000;padding: 8px 8px;width: 20%;">
                    <p style="margin: 3px 0 0 0;">{{ $docA1 ?? '' }}</p>
                </td>
                <td style="background-color:#d3d3d3;"></td>
                <td style="border: 1px solid #000;padding: 8px 8px;width: 25%;">
                    <p style="margin: 3px 0 0 0;">{{ $docB ?? '' }}</p>
                </td>
                <td style="border: 1px solid #000;padding: 8px 8px;width: 25%;">
                    <p style="margin: 3px 0 0 0;">{{ $docC ?? '' }}</p>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 5px 8px;background-color: #d3d3d3;">Issuing Authority</td>
                <td style="border: 1px solid #000;padding: 5px 8px;">
                    <p style="margin: 3px 0 0 0;">{{ $formI9?->list_a_issuing_authority_1 ?? '' }}</p>
                </td>
                <td style="background-color:#d3d3d3;"></td>
                <td style="border: 1px solid #000;padding: 5px 8px;">
                    <p style="margin: 3px 0 0 0;">{{ $formI9?->list_b_issuing_authority ?? '' }}</p>
                </td>
                <td style="border: 1px solid #000;padding: 5px 8px;">
                    <p style="margin: 3px 0 0 0;">{{ $formI9?->list_c_issuing_authority ?? '' }}</p>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 5px 8px;background-color: #d3d3d3;">Document Number (if any)
                </td>
                <td style="border: 1px solid #000;padding: 5px 8px;">
                    <p style="margin: 3px 0 0 0;">{{ $formI9?->list_a_document_number_1 ?? '' }}</p>
                </td>
                <td style="background-color:#d3d3d3;"></td>
                <td style="border: 1px solid #000;padding: 5px 8px;">
                    <p style="margin: 3px 0 0 0;">{{ $formI9?->list_b_document_number ?? '' }}</p>
                </td>
                <td style="border: 1px solid #000;padding: 5px 8px;">
                    <p style="margin: 3px 0 0 0;">{{ $formI9?->list_c_document_number ?? '' }}</p>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 5px 8px;background-color: #d3d3d3;">Expiration Date (if any)
                </td>
                <td style="border: 1px solid #000;padding: 5px 8px;">
                    <p style="margin: 3px 0 0 0;">
                        {{ $formI9?->list_a_expiration_date_1 ? $formatDate($formI9->list_a_expiration_date_1) : '-' }}
                    </p>
                </td>
                <td style="background-color:#d3d3d3;"></td>
                <td style="border: 1px solid #000;padding: 5px 8px;">
                    <p style="margin: 3px 0 0 0;">
                        {{ $formI9?->list_b_expiration_date ? $formatDate($formI9->list_b_expiration_date) : '-' }}
                    </p>
                </td>
                <td style="border: 1px solid #000;padding: 5px 8px;">
                    <p style="margin: 3px 0 0 0;">
                        {{ $formI9?->list_c_expiration_date ? $formatDate($formI9->list_c_expiration_date) : '-' }}
                    </p>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 5px 8px;background-color: #d3d3d3;"><b>Document Title 2 (if
                        any)</b></td>
                <td style="border: 1px solid #000;padding: 5px 8px;">
                    <p style="margin: 3px 0 0 0;">{{ $formI9?->list_a_doc_title_2 ?? '' }}</p>
                </td>
                <td style="background-color:#d3d3d3;padding-left: 8px;" colspan="3"><b>Additional Information</b>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 5px 8px;background-color: #d3d3d3;">Issuing Authority</td>
                <td style="border: 1px solid #000;padding: 5px 8px;">
                    <p style="margin: 3px 0 0 0;">{{ $formI9?->list_a_issuing_authority_2 ?? '' }}</p>
                </td>
                <td style="border: 1px solid #000;padding: 5px 8px;" colspan="3" rowspan="7">
                    <p style="margin: 3px 0 0 0;min-height: 80px;">{{ $formI9?->additional_information ?? '' }}</p>
                    <p style="margin: 5px 0 0 0;font-size: 8px;"><span
                            class="i9-checkbox @if ($formI9?->alternative_procedure) checked @endif">
                            @if ($formI9?->alternative_procedure)
                                &#10003;
                            @endif
                        </span> Check here if you used an alternative procedure authorized by DHS to examine documents.
                    </p>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 5px 8px;background-color: #d3d3d3;">Document Number (if any)
                </td>
                <td style="border: 1px solid #000;padding: 5px 8px;">
                    <p style="margin: 3px 0 0 0;">{{ $formI9?->list_a_document_number_2 ?? '' }}</p>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 5px 8px;background-color: #d3d3d3;">Expiration Date (if any)
                </td>
                <td style="border: 1px solid #000;padding: 5px 8px;">
                    <p style="margin: 3px 0 0 0;">
                        {{ $formI9?->list_a_expiration_date_2 ? $formatDate($formI9->list_a_expiration_date_2) : '' }}
                    </p>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 5px 8px;background-color: #d3d3d3;"><b>Document Title 3 (if
                        any)</b></td>
                <td style="border: 1px solid #000;padding: 5px 8px;">
                    <p style="margin: 3px 0 0 0;">{{ $formI9?->list_a_doc_title_3 ?? '' }}</p>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 5px 8px;background-color: #d3d3d3;">Issuing Authority</td>
                <td style="border: 1px solid #000;padding: 5px 8px;">
                    <p style="margin: 3px 0 0 0;">{{ $formI9?->list_a_issuing_authority_3 ?? '' }}</p>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 5px 8px;background-color: #d3d3d3;">Document Number (if any)
                </td>
                <td style="border: 1px solid #000;padding: 5px 8px;">
                    <p style="margin: 3px 0 0 0;">{{ $formI9?->list_a_document_number_3 ?? '' }}</p>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 5px 8px;background-color: #d3d3d3;">Expiration Date (if any)
                </td>
                <td style="border: 1px solid #000;padding: 5px 8px;">
                    <p style="margin: 3px 0 0 0;">
                        {{ $formI9?->list_a_expiration_date_3 ? $formatDate($formI9->list_a_expiration_date_3) : '' }}
                    </p>
                </td>
            </tr>
        </table>

        <table style="width: 100%;border-collapse: collapse;font-size: 9px;">
            <tr>
                <td style="border: 1px solid #000;padding: 4px 8px;width: 50%;" colspan="2"><b>Certification: I
                        attest, under penalty of perjury, that (1) I have examined the documentation presented by the
                        above-named employee, (2) the above-listed documentation appears to be genuine and to relate to
                        the employee named, and (3) to the best of my knowledge, the employee is authorized to work in
                        the United States.</b></td>
                <td style="border: 1px solid #000;padding: 4px 8px;"><label>First Day of Employment
                        (mm/dd/yyyy):</label>
                    <div class="i9-field">
                        <p style="margin: 3px 0 0 0;">
                            {{ $formatDate($formI9?->first_day_employment ?? $employee?->hire_date) }}</p>
                    </div>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 4px 8px;"><label>Last Name, First Name and Title of Employer
                        or Authorized Representative</label>
                    <div class="i9-field">
                        <p style="margin: 5px 0 0 0;">{{ $formI9?->employer_name ?? '' }}</p>
                    </div>
                </td>
                <td style="border: 1px solid #000;padding: 4px 8px;"><label>Signature of Employer or Authorized
                        Representative</label>
                    <div class="i9-field">
                        <p style="margin: 5px 0 0 0;">{{ $formI9?->employer_signature ?? '' }}</p>
                    </div>
                </td>
                <td style="border: 1px solid #000;padding: 4px 8px;"><label>Today's Date (mm/dd/yyyy)</label>
                    <div class="i9-field">
                        <p style="margin: 5px 0 0 0;">{{ $formatDate($formI9?->employer_today_date) }}</p>
                    </div>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 4px 8px;"><label style="font-size: 9px;">Employer's
                        Business or Organization Name</label>
                    <div class="i9-field">
                        <p style="margin: 3px 0 0 0;">{{ $formI9?->employer_business_name ?? '' }}</p>
                    </div>
                </td>
                <td style="border: 1px solid #000;padding: 4px 8px;" colspan="2"><label>Employer's Business or
                        Organization Address, City or Town, State, ZIP Code</label>
                    <div class="i9-field">
                        <p style="margin: 3px 0 0 0;">{{ $formI9?->employer_business_address ?? '' }}</p>
                    </div>
                </td>
            </tr>
        </table>
        <label
            style="width: 100%;font-size: 9px;text-align: center;display: inline-block;border-bottom: 1px solid;">For
            reverification or rehire, complete <a href="http://www.uscis.gov/I-9" target="_blank"><b>Supplement B,
                    Reverification and Rehire</b></a> on Page 4.</label>
        </div>
        <div class="i9-page-footer"><label>Form I-9 Edition 01/20/25</label><label>Page 1 of 4</label></div>
        </div>
    </div>

    <div class="inner i9-page-2" style="page-break-after: always; margin-top: 20px;">
        <div class="i9-page-inner">
        <div class="i9-page-content">
        <div class="header" style="text-align: center; max-width: 650px; margin: auto; margin-bottom: 15px;">
            <h2 style="margin-top: 0; margin-bottom: 5px; font-size: 18px; font-weight: 600;">LISTS OF ACCEPTABLE
                DOCUMENTS</h2>
            <p style="margin: 0;font-size: 13px;line-height: 13px;">All documents containing an expiration date must be
                unexpired.</p>
            <p style="margin: 0;font-size: 13px;line-height: 13px;">* Documents extended by the issuing authority are
                considered unexpired.</p>
            <p style="margin: 0;font-size: 13px;line-height: 13px;margin-bottom: 5px;">Employees may present one
                selection from List A or a<br>combination of one selection from List B and one selection from List C.
            </p>
            <h4 style="margin: 0; margin-bottom: 5px; font-size: 13px; font-weight: 600;">Examples of many of these
                documents appear in the Handbook for Employers (M-274).</h4>
        </div>
        <table style="width: 100%; border-collapse: collapse; font-size: 10px; line-height: 10px;" border="1">
            <thead style="text-align: center;">
                <tr>
                    <th style="border: 1px solid #000; font-size: 11px; width: 30%;padding: 5px;">LIST A<br /><span
                            class="list-title">Documents that Establish Both Identity<br />and Employment
                            Authorization</span></th>
                    <th style="border: 1px solid #000; font-size: 11px; padding: 5px;width: 3%;">OR</th>
                    <th style="font-size: 11px; padding: 5px;">
                        <div>
                            <div>LIST B</div><span class="list-title">Documents that Establish Identity</span>
                        </div>AND
                    </th>
                    <th style="font-size: 11px; width: 33.33%;padding: 5px;">LIST C<br /><span
                            class="list-title">Documents that Establish Employment Authorization</span></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="border: 1px solid #000;">
                        <ol>
                            <li><b>1.</b> U.S. Passport or U.S. Passport Card</li>
                            <li><b>2.</b> Permanent Resident Card or Alien Registration Receipt Card (Form I-551)</li>
                            <li><b>3.</b> Foreign passport that contains a temporary I-551 stamp or temporary I-551
                                printed notation on a machine-readable immigrant visa</li>
                            <li><b>4.</b> Employment Authorization Document that contains a photograph (Form I-766)</li>
                            <li><b>5.</b> For an individual temporarily authorized to work for a specific employer: <ol
                                    type="a">
                                    <li style="border-bottom: none;"><b>a.</b> Foreign passport; and</li>
                                    <li style="border-bottom: none;"><b>b.</b> Form I-94 or Form I-94A that has: <ol
                                            type="1">
                                            <li style="border-bottom: none;"><b>(1).</b> The same name as the passport;
                                                and</li>
                                            <li style="border-bottom: none;"><b>(2).</b> An endorsement of the
                                                individual's status or parole as long as it has not expired and the
                                                proposed employment is not in conflict with any restrictions or
                                                limitations.</li>
                                        </ol>
                                    </li>
                                </ol>
                            </li>
                            <li><b>6.</b> Passport from the Federated States of Micronesia (FSM) or the Republic of the
                                Marshall Islands (RMI) with Form I-94/I-94A indicating nonimmigrant admission under the
                                Compact of Free Association.</li>
                        </ol>
                    </td>
                    <td style="text-align: center; font-weight: bold; vertical-align: middle;"></td>
                    <td style="border: 1px solid #000;vertical-align: top;">
                        <ol>
                            <li><b>1.</b> Driver's license or ID card issued by a State or outlying possession of the
                                U.S. with a photograph or identifying information</li>
                            <li><b>2.</b> ID card issued by federal, state, or local agencies with photo or identifying
                                information</li>
                            <li><b>3.</b> School ID card with a photograph</li>
                            <li><b>4.</b> Voter's registration card</li>
                            <li><b>5.</b> U.S. Military card or draft record</li>
                            <li><b>6.</b> Military dependent's ID card</li>
                            <li><b>7.</b> U.S. Coast Guard Merchant Mariner Card</li>
                            <li><b>8.</b> Native American tribal document</li>
                            <li><b>9.</b> Driver's license issued by a Canadian government authority</li>
                            <li style="text-align: center; padding: 8px;"><b>For persons under age 18 who are unable to
                                    present a document listed above:</b></li>
                            <li><b>10.</b> School record or report card</li>
                            <li><b>11.</b> Clinic, doctor, or hospital record</li>
                            <li><b>12.</b> Day-care or nursery school record</li>
                        </ol>
                    </td>
                    <td style="border: 1px solid #000;vertical-align: top;">
                        <ol>
                            <li><b>1.</b> Social Security Account Number card, unless it states: <ol type="a">
                                    <li style="border-bottom: none;"><b>(1)</b> NOT VALID FOR EMPLOYMENT</li>
                                    <li style="border-bottom: none;"><b>(2)</b> VALID FOR WORK ONLY WITH INS
                                        AUTHORIZATION</li>
                                    <li style="border-bottom: none;"><b>(3)</b> VALID FOR WORK ONLY WITH DHS
                                        AUTHORIZATION</li>
                                </ol>
                            </li>
                            <li><b>2.</b> Certification of report of birth issued by the Department of State (Forms
                                DS-1350, FS-545, FS-240)</li>
                            <li><b>3.</b> Original or certified copy of birth certificate issued by a U.S. entity
                                bearing an official seal</li>
                            <li><b>4.</b> Native American tribal document</li>
                            <li><b>5.</b> U.S. Citizen ID Card (Form I-197)</li>
                            <li><b>6.</b> Identification Card for Use of Resident Citizen in the U.S. (Form I-179)</li>
                            <li><b>7.</b> Employment authorization document issued by the Department of Homeland
                                Security. For examples, see
                                <a href="https://www.uscis.gov/i-9-central/form-i-9-resources/handbook-for-employers-m-274/70-evidence-of-employment-authorization-for-certain-categories"
                                    target="_blank">Section 7</a> and <a
                                    href="https://www.uscis.gov/i-9-central/form-i-9-resources/handbook-for-employers-m-274/130-acceptable-documents-for-verifying-employment-authorization-and-identity/133-list-c-documents-that-establish-employment-authorization"
                                    target="_blank">Section 13</a> of the M-274 on <a
                                    href="https://www.uscis.gov/i-9-central"
                                    target="_blank">uscis.gov/i-9-central.</a>
                                <br />
                                The Form I-766, Employment Authorization Document, is
                                a List A, not List C document.
                            </li>
                        </ol>
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="header" style="text-align: center; max-width: 650px; margin: 15px auto;">
            <h4 style="margin: 0; margin-bottom: 5px; font-size: 13px; font-weight: 600;">Acceptable Receipts</h4>
            <p style="margin: 0;font-size: 13px;margin-bottom: 5px;">May be presented in lieu of a document listed
                above for a temporary period.</p>
            <p style="margin: 0;font-size: 13px;">For receipt validity dates, see the M-274.</p>
        </div>
        <table style="width: 100%; border-collapse: collapse; font-size: 10px;" border="1">
            <tbody>
                <tr>
                    <td style="border: 1px solid #000; width: 30%;vertical-align: top;">
                        <ol style="list-style: disc; padding: 0 20px;">
                            <li style="border-bottom: none; padding-left: 0;">Receipt for a replacement of a lost,
                                stolen, or damaged List A document</li>
                            <li style="border-bottom: none; padding-left: 0;">Form I-94 issued to a lawful permanent
                                resident that contains an I-551 stamp and a photograph of the individual</li>
                            <li style="border-bottom: none; padding-left: 0;">Form I-94 with "RE" notation or refugee
                                stamp issued to a refugee.</li>
                        </ol>
                    </td>
                    <td
                        style="text-align: center; font-weight: bold; vertical-align: middle; padding: 0 5px;width: 3%;">
                        OR</td>
                    <td style="border: 1px solid #000; width: 33.33%;vertical-align: top;">
                        <ol>
                            <li>Receipt for a replacement of a lost, stolen, or damaged List B document.</li>
                        </ol>
                    </td>
                    <td style="border: 1px solid #000;vertical-align: top;">
                        <ol>
                            <li>Receipt for a replacement of a lost, stolen, or damaged List C document.</li>
                        </ol>
                    </td>
                </tr>
            </tbody>
        </table>
        <label
            style="width: 100%; font-size: 10px;border-bottom: 1px solid;display: inline-block;padding: 10px 0;margin-bottom: 10px;">*Refer
            to the Employment Authorization Extensions page on <a
                href="https://www.uscis.gov/i-9-central/form-i-9-acceptable-documents/employment-authorization-extensions"
                target="_blank"><b>I-9 Central</b></a> for more information.</label>
        </div>
        <div class="i9-page-footer"><label>Form I-9 Edition 01/20/25</label><label>Page 2 of 4</label></div>
        </div>
    </div>

    <div class="inner i9-page-3" style="page-break-after: always;">
        <div class="i9-page-inner">
        <div class="i9-page-content">
        <table style="width: 100%;">
            <tr>
                <td width="15%">
                    @if ($logoPath)
                        <img src="{{ $logoPath }}" style="max-width: 60px;">
                    @endif
                </td>
                <td width="65%" style="text-align: center;">
                    <h2 style="margin-top: 0; margin-bottom: 5px; font-size: 15px; font-weight: 600;">Supplement A,
                    </h2>
                    <h2 style="margin-top: 0; margin-bottom: 5px; font-size: 15px; font-weight: 600;">Preparer and/or
                        Translator Certification for Section 1</h2>
                    <h4 style="margin: 0; margin-bottom: 5px; font-size: 12px; font-weight: 600;">Department of
                        Homeland Security</h4>
                    <p style="margin: 0;font-size: 12px;">U.S. Citizenship and Immigration Services</p>
                </td>
                <td width="20%" style="text-align: center;">
                    <h5 style="margin: 0; font-size: 14px; font-weight: 600;">USCIS</h5>
                    <h5 style="margin: 0; font-size: 14px; font-weight: 600;">Form I-9</h5>
                    <h5 style="margin: 0; font-size: 14px; font-weight: 600;">Supplement A</h5>
                    <p style="margin: 0; font-size: 12px;">OMB No.1615-0047</p>
                    <p style="margin: 0; font-size: 12px;">Expires 05/31/2027</p>
                </td>
            </tr>
        </table>
        <hr style="border-color: #000; border-width: thick; opacity: 1;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 9px;">Last Name (Family
                        Name) from Section 1.</label>
                    <div class="i9-field">
                        <p style="margin: 3px 0 0 0;">
                            {{ $section1['last_name'] ?? '' }}</p>
                    </div>
                </td>
                <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 9px;">First Name (Given
                        Name) from Section 1.</label>
                    <div class="i9-field">
                        <p style="margin: 3px 0 0 0;">
                            {{ $section1['first_name'] ?? '' }}</p>
                    </div>
                </td>
                <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 9px;">Middle initial
                        (if any) from Section 1.</label>
                    <div class="i9-field">
                        <p style="margin: 3px 0 0 0;">
                            {{ $section1['middle_initial'] ?? '' }}</p>
                    </div>
                </td>
            </tr>
        </table>
        <div style="font-size: 10px; line-height: 10px; margin: 15px 0;"><b>Instructions:</b> This supplement must be
            completed by any preparer and/or translator who assists an employee in completing Section 1 of Form I-9. The
            preparer and/or translator must enter
            the employee's name in the spaces provided above. Each preparer or translator must complete, sign, and date
            a separate certification area. Employers must retain completed supplement
            sheets with the employee's completed Form I-9..</div>
            @forelse(($preparer_translators ?? collect()) as $pt)
            <div style="font-size: 10px; line-height: 10px; margin-bottom: 5px;"><b>I attest, under penalty of perjury,
                    that I have assisted in the completion of Section 1 of this form and that to the best of my knowledge
                    the information is true and correct.</b></div>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <tr>
                    <td style="border: 1px solid #000; padding: 4px 8px;" colspan="3"><label
                            style="font-size: 9px;">Signature of Preparer or Translator</label>
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">{{ $pt['signature'] ?? '' }}</p>
                        </div>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 9px;">Date
                            (mm/dd/yyyy)</label>
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">{{ $formatDate($pt['signature_date']) }}</p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 9px;">Last Name
                            (Family Name)</label>
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">{{ $pt['last_name'] ?? '' }}</p>
                        </div>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 8px;" colspan="2"><label
                            style="font-size: 9px;">First Name (Given Name)</label>
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">{{ $pt['first_name'] ?? '' }}</p>
                        </div>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 9px;">Middle
                            Initial (if any)</label>
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">{{ $pt['middle_initial'] ?? '' }}</p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 9px;">Address
                            (Street Number and Name)</label>
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">{{ $pt['address'] ?? '' }}</p>
                        </div>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 9px;">City or
                            Town</label>
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">{{ $pt['city'] ?? '' }}</p>
                        </div>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 9px;">State</label>
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">{{ $pt['state'] ?? '' }}</p>
                        </div>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 9px;">ZIP
                            Code</label>
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">{{ $pt['zip_code'] ?? '' }}</p>
                        </div>
                    </td>
                </tr>
            </table>
        @empty
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                <tr>
                    <td style="border: 1px solid #000; padding: 4px 8px;" colspan="3"><label
                            style="font-size: 9px;">Signature of Preparer or Translator</label>
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">&nbsp;</p>
                        </div>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 9px;">Date
                            (mm/dd/yyyy)</label>
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">&nbsp;</p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 9px;">Last Name
                            (Family Name)</label>
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">&nbsp;</p>
                        </div>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 8px;" colspan="2"><label
                            style="font-size: 9px;">First Name (Given Name)</label>
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">&nbsp;</p>
                        </div>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 9px;">Middle
                            Initial (if any)</label>
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">&nbsp;</p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 9px;">Address
                            (Street Number and Name)</label>
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">&nbsp;</p>
                        </div>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 9px;">City or
                            Town</label>
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">&nbsp;</p>
                        </div>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 9px;">State</label>
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">&nbsp;</p>
                        </div>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 9px;">ZIP
                            Code</label>
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">&nbsp;</p>
                        </div>
                    </td>
                </tr>
            </table>
        @endforelse
        </div>
        <div class="i9-page-footer" style="margin-top: 30px;"><label>Form I-9 Edition 01/20/25</label><label>Page 3 of 4</label></div>
        </div>
    </div>

    <div class="inner i9-page-4">
        <div class="i9-page-inner">
        <div class="i9-page-content">
        <table style="width: 100%;">
            <tr>
                <td width="15%">
                    @if ($logoPath)
                        <img src="{{ $logoPath }}" style="max-width: 60px;">
                    @endif
                </td>
                <td width="65%" style="text-align: center;">
                    <h2 style="margin-top: 0; margin-bottom: 5px; font-size: 15px; font-weight: 600;">Supplement B,
                    </h2>
                    <h2 style="margin-top: 0; margin-bottom: 5px; font-size: 15px; font-weight: 600;">Reverification
                        and Rehire (formerly Section 3)</h2>
                    <h4 style="margin: 0; margin-bottom: 5px; font-size: 12px; font-weight: 600;">Department of
                        Homeland Security</h4>
                    <p style="margin: 0;font-size: 12px;">U.S. Citizenship and Immigration Services</p>
                </td>
                <td width="20%" style="text-align: center;">
                    <h5 style="margin: 0; font-size: 14px; font-weight: 600;">USCIS</h5>
                    <h5 style="margin: 0; font-size: 14px; font-weight: 600;">Form I-9</h5>
                    <h5 style="margin: 0; font-size: 14px; font-weight: 600;">Supplement B</h5>
                    <p style="margin: 0; font-size: 12px;">OMB No. 1615-0047</p>
                    <p style="margin: 0; font-size: 12px;">Expires 05/31/2027</p>
                </td>
            </tr>
        </table>
        <hr style="border-color: #000; border-width: thick; opacity: 1;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 9px;">Last Name (Family
                        Name) from Section 1.</label>
                    <div class="i9-field">
                        <p style="margin: 3px 0 0 0;">
                            {{ $section1['last_name'] ?? '' }}</p>
                    </div>
                </td>
                <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 9px;">First Name (Given
                        Name) from Section 1.</label>
                    <div class="i9-field">
                        <p style="margin: 3px 0 0 0;">
                            {{ $section1['first_name'] ?? '' }}</p>
                    </div>
                </td>
                <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 9px;">Middle initial
                        (if any) from Section 1.</label>
                    <div class="i9-field">
                        <p style="margin: 3px 0 0 0;">
                            {{ $section1['middle_initial'] ?? '' }}</p>
                    </div>
                </td>
            </tr>
        </table>
        <div style="font-size: 9px; line-height: 9px; margin: 5px 0;">Instructions:  This supplement replaces Section 3 on the previous version of Form I-9. Only use this page if your employee requires reverification, is rehired within three years of the date the original Form I-9
            was completed, or provides proof of a legal name change. Enter the employee's name in the fields above. Use a new section for each reverification or rehire. Review the Form I-9 instructions before completing
            this page. Keep this page as part of the employee's Form I-9 record. Additional guidance can be found in the.
            <br>
            <a href="https://www.uscis.gov/i-9-central/form-i-9-resources/handbook-for-employers-m-274" target="_blank"><b>Handbook for Employers: Guidance for Completing Form I-9 (M-274)</b></a>
        </div>
        @forelse(($reverifications ?? collect()) as $rv)
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
                <tr>
                    <td style="border: 1px solid #000; padding: 0 8px; background-color: #d3d3d3;"><label
                            style="font-size: 9px;">Date of Rehire (if applicable)</label><br/>
                        <label style="font-size: 8px;">Date (mm/dd/yyyy)</label></td>
                    <td style="border: 1px solid #000; padding: 0 8px; background-color: #d3d3d3;"><label
                            style="font-size: 9px;">New Name (if applicable)</label><br/>
                        <label style="font-size: 8px;">Last Name (Family Name)</label></td>
                    <td style="border: 1px solid #000; padding: 0 8px; background-color: #d3d3d3;"><label
                            style="font-size: 8px;">First Name (Given Name)</label></td>
                    <td style="border: 1px solid #000; padding: 0 8px; background-color: #d3d3d3;"><label
                            style="font-size: 8px;">Middle Initial</label></td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px 8px;">
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">{{ $formatDate($rv['rehire_date']) }}</p>
                        </div>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 8px;">
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">{{ $rv['new_last_name'] ?? '' }}</p>
                        </div>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 8px;">
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">{{ $rv['new_first_name'] ?? '' }}</p>
                        </div>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 8px;">
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">{{ $rv['new_middle_initial'] ?? '' }}</p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px 8px; background-color: #f0f0f0;" colspan="4">
                        <b style="font-size: 9px;">Reverification:</b>
                        <span style="font-size: 9px;"> If the employee requires reverification, your employee can choose to present any acceptable List A or List C documentation to show continued employment authorization. Enter the document information in the spaces below.</span>
                    </td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px 8px;" colspan="2"><label style="font-size: 9px;">Document Title</label>
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">{{ $rv['document_title'] ?? '' }}</p>
                        </div>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 9px;">Document Number (if any)</label>
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">{{ $rv['document_number'] ?? '' }}</p>
                        </div>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 9px;">Expiration Date (if any) (mm/dd/yyyy)</label>
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">{{ $formatDate($rv['expiration_date']) }}</p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px 8px;" colspan="4">
                        <b style="font-size: 9px;">I attest, under penalty of perjury, that to the best of my knowledge, this employee is authorized to work in the United States, and if the employee presented documentation, the documentation I examined appears to be genuine and to relate to the individual who presented it.</b>
                    </td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 9px;">Name of Employer or Authorized Representative</label>
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">{{ $rv['employer_representative_name'] ?? '' }}</p>
                        </div>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 9px;">Signature of Employer or Authorized Representative</label>
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">{{ $rv['employer_signature'] ?? '' }}</p>
                        </div>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 8px;" colspan="2"><label style="font-size: 9px;">Today's Date (mm/dd/yyyy)</label>
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">{{ $formatDate($rv['today_date']) }}</p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px 8px;" colspan="3"><label style="font-size: 9px;">Additional Information (Initial and date each notation.)</label>
                        <div class="i9-field">
                            <p style="margin: 3px 0 0 0;">{{ $rv['additional_information'] ?? '' }}</p>
                        </div>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 8px; width: 1%; white-space: nowrap; vertical-align: top;">
                        <span style="font-size: 10px;">{{ !empty($rv['alternative_procedure_dhs']) ? '☑' : '☐' }}</span>
                        <label style="font-size: 9px;"> Check here if you used an alternative procedure authorized by DHS to examine documents.</label>
                    </td>
                </tr>
            </table>
        @empty
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
                <tr>
                    <td style="border: 1px solid #000; padding: 0 8px; background-color: #d3d3d3;"><label
                            style="font-size: 9px;">Date of Rehire (if applicable)</label><br/>
                        <label style="font-size: 8px;">Date (mm/dd/yyyy)</label></td>
                    <td style="border: 1px solid #000; padding: 0 8px; background-color: #d3d3d3;"><label
                            style="font-size: 9px;">New Name (if applicable)</label><br/>
                        <label style="font-size: 8px;">Last Name (Family Name)</label></td>
                    <td style="border: 1px solid #000; padding: 0 8px; background-color: #d3d3d3;"><label
                            style="font-size: 8px;">First Name (Given Name)</label></td>
                    <td style="border: 1px solid #000; padding: 0 8px; background-color: #d3d3d3;"><label
                            style="font-size: 8px;">Middle Initial</label></td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px 8px;">
                        <div class="i9-field"><p style="margin: 3px 0 0 0;">&nbsp;</p></div>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 8px;">
                        <div class="i9-field"><p style="margin: 3px 0 0 0;">&nbsp;</p></div>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 8px;">
                        <div class="i9-field"><p style="margin: 3px 0 0 0;">&nbsp;</p></div>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 8px;">
                        <div class="i9-field"><p style="margin: 3px 0 0 0;">&nbsp;</p></div>
                    </td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px 8px; background-color: #f0f0f0;" colspan="4">
                        <b style="font-size: 9px;">Reverification:</b>
                        <span style="font-size: 9px;"> If the employee requires reverification, your employee can choose to present any acceptable List A or List C documentation to show continued employment authorization. Enter the document information in the spaces below.</span>
                    </td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px 8px;" colspan="2"><label style="font-size: 9px;">Document Title</label>
                        <div class="i9-field"><p style="margin: 3px 0 0 0;">&nbsp;</p></div>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 9px;">Document Number (if any)</label>
                        <div class="i9-field"><p style="margin: 3px 0 0 0;">&nbsp;</p></div>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 9px;">Expiration Date (if any) (mm/dd/yyyy)</label>
                        <div class="i9-field"><p style="margin: 3px 0 0 0;">&nbsp;</p></div>
                    </td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px 8px;" colspan="4">
                        <b style="font-size: 9px;">I attest, under penalty of perjury, that to the best of my knowledge, this employee is authorized to work in the United States, and if the employee presented documentation, the documentation I examined appears to be genuine and to relate to the individual who presented it.</b>
                    </td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 9px;">Name of Employer or Authorized Representative</label>
                        <div class="i9-field"><p style="margin: 3px 0 0 0;">&nbsp;</p></div>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 8px;"><label style="font-size: 9px;">Signature of Employer or Authorized Representative</label>
                        <div class="i9-field"><p style="margin: 3px 0 0 0;">&nbsp;</p></div>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 8px;" colspan="2"><label style="font-size: 9px;">Today's Date (mm/dd/yyyy)</label>
                        <div class="i9-field"><p style="margin: 3px 0 0 0;">&nbsp;</p></div>
                    </td>
                </tr>
                <tr>
                    <td style="border: 1px solid #000; padding: 4px 8px;" colspan="3"><label style="font-size: 9px;">Additional Information (Initial and date each notation.)</label>
                        <div class="i9-field"><p style="margin: 3px 0 0 0;">&nbsp;</p></div>
                    </td>
                    <td style="border: 1px solid #000; padding: 4px 8px; width: 1%; white-space: nowrap; vertical-align: top;">
                        <span style="font-size: 9px;">☐</span>
                        <label style="font-size: 9px;"> Check here if you used an alternative procedure authorized by DHS to examine documents.</label>
                    </td>
                </tr>
            </table>
        @endforelse
        </div>
        <div class="i9-page-footer" style="margin-top: 5px;"><label>Form I-9 Edition 01/20/25</label><label>Page 4 of 4</label></div>
        </div>
    </div>
</body>

</html>
