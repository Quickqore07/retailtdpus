<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>W-4</title>
    <style>
        @page { margin: 60px 20px; }
        body { margin: 0; padding: 0; font-size: 10px; line-height: 13px; }
        .w4-field { min-height: 12px; border-bottom: 1px solid #000; margin-bottom: 2px; }
        .w4-field p { margin: 2px 0 0 0; font-size: 10px; line-height: 13px; }
        .w4-radio { display: inline-block; width: 8px; height: 8px; border: 1px solid #000; border-radius: 50%; vertical-align: middle; margin-right: 2px; }
        .w4-radio.filled { background: #000; }
        .w4-checkbox { display: inline-block; width: 10px; height: 10px; border: 1px solid #000; vertical-align: middle; margin-right: 4px; text-align: center; line-height: 13px; font-size: 10px; }
        .w4-checkbox.checked { font-weight: bold; }
    </style>
</head>
<body>
@php
    $w4 = $formW4 ?? null;
    $ob = $onboarding ?? null;
    $company = $ob?->company ?? null;
    $employee = $ob?->employee ?? null;
    $firstName = $ob?->applicant_first_name ?? '';
    $lastName = $ob?->applicant_last_name ?? '';
    $ssn = $employee?->ssn ?? $ob?->applicant_ssn ?? '';
    $address = $ob?->applicant_address ?? '';
    $city = $ob?->city ?? '';
    $state = $ob?->state ?? '';
    $zipCode = $ob?->zip_code ?? '';
    $cityStateZip = trim(implode(', ', array_filter([$city, $state, $zipCode])));
    $singleOrMarried = $w4?->single_or_married ? 1 :( $w4?->married_filing ? 2 : ($w4?->head_of_household ? 3 : null));
    $multiJobs = $w4?->multiple_jobs_and_spouse_works ?? null;
    $formatDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('m-d-Y') : '';
    $empDate = $w4?->employee_date ? $formatDate($w4->employee_date) : ($ob?->doj ? $formatDate($ob->doj) : date('m-d-Y'));
    $ein = $company?->employer_identification_number ?? '';
@endphp

<div class="inner w4-page-1" style="page-break-after: always;">
    <table style="width:100%; border-bottom: 1px solid; font-size: 11px; line-height: 13px; border: none;">
        <tr>
            <td style="width:20%; border-right: 1px solid;">
                <div style="font-size: 14px; font-weight: bold;">Form</div>
                <div style="font-size: 26px; font-weight: bold; margin-left: 50px;">W-4</div>
                <div style="font-size: 9px; line-height: 9px; margin-top: 8px;">Department of the Treasury</div>
                <div style="font-size: 9px; line-height: 9px;">Internal Revenue Service</div>
            </td>
            <td style="width:60%;">
                <div style="margin-top:0; margin-bottom: 5px; font-size: 18px; font-weight: 600; text-align: center;">Employee's Withholding Certificate</div>
                <div style="font-size: 9px; line-height: 9px; text-align: center;"><b>Complete Form W-4 so that your employer can withhold the correct federal income tax from your pay.</b></div>
                <div style="font-size: 9px; line-height: 9px; text-align: center;"><b>Give Form W-4 to your employer.</b></div>
                <div style="font-size: 9px; line-height: 9px; text-align: center;"><b>Your withholding is subject to review by the IRS.</b></div>
            </td>
            <td style="width:20%; border-left: 1px solid; text-align: right;">
                <div style="font-size: 9px; border-bottom: 2px solid; margin-bottom: 15px;">OMB No. 1545-0074</div>
                <div style="font-size: 36px; font-weight: bold;margin-top: 20px;">2025</div>
            </td>
        </tr>
    </table>

    <table style="border-collapse: collapse; width: 100%; font-size: 11px; line-height: 13px;">
        <tr>
            <td style="border: 1px solid #000; padding: 1px 4px; font-weight: bold; width: 10%; vertical-align: baseline; font-size: 12px;" rowspan="4">Step 1:<br>Enter Personal Information</td>
            <td style="border: 1px solid #000; padding: 1px 4px;">
                <label style="display: inline-block;">(a) First name and middle initial</label>
                <div class="w4-field"><p>{{ $firstName }}</p></div>
            </td>
            <td style="border: 1px solid #000; padding: 1px 4px;">
                <label>Last name</label>
                <div class="w4-field"><p>{{ $lastName }}</p></div>
            </td>
            <td style="border: 1px solid #000; padding: 1px 4px;">
                <label>(b) Social security number</label>
                <div class="w4-field"><p>{{ $ssn }}</p></div>
            </td>
        </tr>
        <tr>
            <td style="border: 1px solid #000; padding: 1px 4px;" colspan="2">
                <label>Address</label>
                <div class="w4-field"><p>{{ $address }}</p></div>
            </td>
            <td style="border: 1px solid #000; padding: 1px 4px; width: 20%; vertical-align: baseline; font-size: 9px; line-height: 10px;" rowspan="2">
                <b>Does your name match the name on your social security card?</b> If not, to ensure you get credit for your earnings, contact SSA at 800-772-1213 or go to <a href="https://www.ssa.gov" target="_blank" style="color: #0066cc;">www.ssa.gov</a>.
            </td>
        </tr>
        <tr>
            <td style="border: 1px solid #000; padding: 1px 4px;" colspan="2">
                <label>City or town, state, and ZIP code</label>
                <div class="w4-field"><p>{{ $cityStateZip }}</p></div>
            </td>
        </tr>
        <tr>
            <td style="border: 1px solid #000; padding: 1px 4px;" colspan="3">
                <b>(c)</b>
                <div style="margin-top:10px"><span class="w4-radio {{ $singleOrMarried == '1' ? 'filled' : '' }}"></span> Single or Married filing separately</div>
                <div style="margin-top:10px"><span class="w4-radio {{ $singleOrMarried == '2' ? 'filled' : '' }}"></span> Married filing jointly or Qualifying surviving spouse</div>
                <div style="margin-top:10px"><span class="w4-radio {{ $singleOrMarried == '3' ? 'filled' : '' }}"></span> Head of household (Check only if you're unmarried and pay more than half the costs of keeping up a home for yourself and a qualifying individual.)</div>
            </td>
        </tr>
    </table>

    <p style="font-size: 11px; line-height: 9px; margin-top: 5px;"><b>TIP:</b> Consider using the estimator at <a href="https://www.irs.gov/W4App" target="_blank" style="color: #0066cc;">www.irs.gov/W4App</a> to determine the most accurate withholding for the rest of the year if: you are completing this form after the beginning of the year; expect to work only part of the year; or have changes during the year in your marital status, number of jobs for you (and/or your spouse if married filing jointly), dependents, other income (not from jobs), deductions, or credits. Have your most recent pay stub(s) from this year available when using the estimator. At the beginning of next year, use the estimator again to recheck your withholding.</p>
    <p style="font-size: 11px; line-height: 9px;"><b>Complete Steps 2–4 ONLY if they apply to you; otherwise, skip to Step 5.</b> See page 2 for more information on each step, who can claim exemption from withholding, and when to use the estimator at <a href="https://www.irs.gov/W4App" target="_blank" style="color: #0066cc;">www.irs.gov/W4App</a>.</p>

    <table border="1" style="border-collapse: collapse; width: 100%; font-size: 11px; line-height: 13px;">
        <tr>
            <td style="padding: 8px; font-weight: bold; width: 10%; border-bottom: none; border-right: none; vertical-align: baseline; font-size: 12px;">Step 2: Multiple Jobs or Spouse Works</td>
            <td style="padding: 10px; border-bottom: none; border-left: none;">
                <p style="font-size: 11px; line-height: 9px;">Complete this step if you (1) hold more than one job at a time, or (2) are married filing jointly and your spouse also works. The correct amount of withholding depends on income earned from all of these jobs.</p>
                <div style="margin-bottom:4px;">Do <b>only one</b> of the following.</div>
                <div style="margin-bottom: 4px;"><span class="w4-radio {{ $multiJobs == '1' ? 'filled' : '' }}"></span> <b>(a)</b> Use the estimator at <a href="https://www.irs.gov/W4App" target="_blank" style="color: #0066cc;">www.irs.gov/W4App</a> for most accurate withholding for this step (and Steps 3–4). If you or your spouse have self-employment income,</div>
                <div style="margin-bottom: 4px;"><span class="w4-radio {{ $multiJobs == '2' ? 'filled' : '' }}"></span> <b>(b)</b> Use the Multiple Jobs Worksheet on page 3 and enter the result in Step 4(c) below</div>
                <div style="margin-bottom: 4px;"><span class="w4-radio {{ $multiJobs == '3' ? 'filled' : '' }}"></span> <b>(c)</b> If there are only two jobs total, you may check this box. Do the same on Form W-4 for the other job. . . . &nbsp;<span class="w4-checkbox"></span></div>
            </td>
        </tr>
        <tr>
            <td colspan="2" style="padding: 8px; border-top: none;"><b>Complete Steps 3–4(b) on Form W-4 for only ONE of these jobs.</b> Leave those steps blank for the other jobs. (Your withholding will be most accurate if you complete Steps 3–4(b) on the Form W-4 for the highest paying job.)</td>
        </tr>
    </table>

    <table border="1" style="border-collapse: collapse; border-top: none; width: 100%;">
        <tr>
            <td style="padding: 8px; font-weight: bold; width: 10%; vertical-align: baseline; font-size: 12px;">Step 3: Claim Dependent and Other Credits</td>
            <td style="padding: 5px; width: 70%; font-size: 11px;">
                <p>If your total income will be $200,000 or less ($400,000 or less if married filing jointly):</p>
                <p style="padding-left: 15px;">Multiply the number of qualifying children under age 17 by $2,000 &nbsp;<b>$</b>&nbsp;<span class="w4-field" style="display:inline-block; width:50px;">{{ $w4->qualifying_children ?? '' }}</span></p>
                <p style="padding-left: 15px;">Multiply the number of other dependents by $500 &nbsp;. . . . . . . . . . . . <b>$</b>&nbsp;<span class="w4-field" style="display:inline-block; width:50px;">{{ $w4->dependents ?? '' }}</span></p>
                <p style="padding-left: 15px; margin-bottom: 0;">Add the amounts above for qualifying children and other dependents. You may add to this the amount of any other credits. Enter the total here</p>
            </td>
            <td style="vertical-align: bottom; width: 10px; border: 1px solid #000; padding: 0 9px;"><b>3</b></td>
            <td style="vertical-align: bottom;"><b>$</b>&nbsp;<div class="w4-field" style="display:inline-block; min-width:60px;"><p>{{ $w4?->total_amount ?? '' }}</p></div></td>
        </tr>
    </table>

    <table border="1" style="border-collapse: collapse; border-top: none; width: 100%;">
        <tr>
            <td rowspan="3" style="padding: 8px; font-weight: bold; width: 10%; vertical-align: baseline; font-size: 12px;">Step 4: (optional): Other Adjustments</td>
            <td style="padding: 5px 8px; width: 70%; font-size: 11px;"><b>(a) Other income (not from jobs).</b> If you want tax withheld for other income you expect this year that won't have withholding, enter the amount of other income here.</td>
            <td style="vertical-align: bottom; width: 28px; font-size: 11px; border: 1px solid #000;"><b>4(a)</b></td>
            <td style="vertical-align: bottom;"><b>$</b>&nbsp;<div class="w4-field" style="display:inline-block; min-width:50px;"><p>{{ $w4?->other_income ?? '' }}</p></div></td>
        </tr>
        <tr>
            <td style="padding: 5px 8px; font-size: 11px;"><b>(b) Deductions.</b> If you expect to claim deductions other than the standard deduction and want to reduce your withholding, use the Deductions Worksheet on page 3 and enter the result here</td>
            <td style="vertical-align: bottom; border: 1px solid #000;"><b>4(b)</b></td>
            <td style="vertical-align: bottom;"><b>$</b>&nbsp;<div class="w4-field" style="display:inline-block; min-width:50px;"><p>{{ $w4?->deductions ?? '' }}</p></div></td>
        </tr>
        <tr>
            <td style="padding: 5px 8px; font-size: 11px;"><b>(c) Extra withholding.</b> Enter any additional tax you want withheld each pay period</td>
            <td style="vertical-align: bottom; border: 1px solid #000;"><b>4(c)</b></td>
            <td style="vertical-align: bottom;"><b>$</b>&nbsp;<div class="w4-field" style="display:inline-block; min-width:50px;"><p>{{ $w4?->extra_withholding ?? '' }}</p></div></td>
        </tr>
    </table>

    <table border="1" style="border-collapse: collapse; border-top: none; width: 100%; font-size: 11px;">
        <tr>
            <td rowspan="2" style="padding: 8px; font-weight: bold; width: 12%; vertical-align: baseline; font-size: 12px;">Step 5:<br>Sign Here</td>
            <td style="padding: 5px;" colspan="2">Under penalties of perjury, I declare that this certificate, to the best of my knowledge and belief, is true, correct, and complete.</td>
        </tr>
        <tr>
            <td style="border-top: none;">
                <div style="padding: 5px;">
                    <div class="w4-field"><p>{{ $w4?->employee_sign ?? '' }}</p></div>
                    <b>Employee's signature</b> (This form is not valid unless you sign it.)
                </div>
            </td>
            <td style="border-top: none;">
                <div style="padding: 5px;">
                    <div class="w4-field"><p>{{ $empDate }}</p></div>
                    <b>Date</b>
                </div>
            </td>
        </tr>
    </table>

    <table border="1" style="border-collapse: collapse; border-top: none; width: 100%; font-size: 11px;">
        <tr>
            <td style="padding: 8px; font-weight: bold; width: 12%; font-size: 12px;">Employers<br>Only</td>
            <td style="padding: 5px 8px;">Employer's name and address <div class="w4-field"><p>{{ $w4?->employer_name ?? $company?->name ?? '' }}</p></div></td>
            <td style="padding: 5px 8px;">First date of employment <div class="w4-field"><p>{{ $w4?->date_of_employment ?? ($ob?->doj ? $formatDate($ob->doj) : date('m-d-Y')) }}</p></div></td>
            <td style="padding: 5px 8px;">Employer identification number (EIN) <div class="w4-field"><p>{{ $ein }}</p></div></td>
        </tr>
    </table>

    <div style="margin-top: 3px; font-size: 11px;">
        <label><b>For Privacy Act and Paperwork Reduction Act Notice, see page 3.</b></label>
        <label style="margin-right: 130px;">Cat. No. 10220Q</label>
        <label>Form <b>W-4</b> (2025)</label>
    </div>
</div>

<div class="inner w4-page-2" style="page-break-after: always;">
    <table style="width:100%; border-bottom: 1px solid; font-size: 11px; line-height: 13px;">
        <tr>
            <td style="width:20%; border-right: 1px solid;">
                <div style="font-size: 14px; font-weight: bold;">Form</div>
                <div style="font-size: 26px; font-weight: bold; margin-left: 50px;">W-4</div>
                <div style="font-size: 9px; line-height: 9px; margin-top: 8px;">Department of the Treasury</div>
                <div style="font-size: 9px; line-height: 9px;">Internal Revenue Service</div>
            </td>
            <td style="width:60%;">
                <div style="margin-top:0; margin-bottom: 5px; font-size: 18px; font-weight: 600; text-align: center;">Employee's Withholding Certificate</div>
                <div style="font-size: 9px; line-height: 9px; text-align: center;"><b>Complete Form W-4 so that your employer can withhold the correct federal income tax from your pay.</b></div>
                <div style="font-size: 9px; line-height: 9px; text-align: center;"><b>Give Form W-4 to your employer.</b></div>
                <div style="font-size: 9px; line-height: 9px; text-align: center;"><b>Your withholding is subject to review by the IRS.</b></div>
            </td>
            <td style="width:20%; border-left: 1px solid; text-align: right;">
                <div style="font-size: 9px; border-bottom: 2px solid; margin-bottom: 15px;">OMB No. 1545-0074</div>
                <div style="font-size: 36px; font-weight: bold; margin-top: 20px;">2025</div>
            </td>
        </tr>
    </table>

    <table style="border-collapse: collapse; border-top: none; width: 100%; font-size: 10px; line-height: 10px; border-top: 2px solid;">
        <tbody>
            <tr>
                <td style="padding-right: 20px; vertical-align: top;" width="50%">
                    <div style="margin:0; font-size: 18px; font-weight: 600; margin-top: 10px;">General Instructions</div>
                    <p style="margin: 4px 0; font-size: 10px; line-height: 12px;">Section references are to the Internal Revenue Code unless otherwise noted.</p>
                    <div style="margin: 8px 0 0 0; font-size: 14px; font-weight: 600;">Future Developments</div>
                    <p style="margin: 4px 0; font-size: 10px; line-height: 12px;">For the latest information about developments related to Form W-4, such as legislation enacted after it was published, go to <a href="https://www.irs.gov/FormW4" target="_blank" style="color: #0066cc;">www.irs.gov/FormW4</a>.</p>
                    <div style="margin: 8px 0 0 0; font-size: 14px; font-weight: 600;">Purpose of Form</div>
                    <p style="margin: 4px 0; font-size: 10px; line-height: 12px;">Complete Form W-4 so that your employer can withhold the correct federal income tax from your pay. If too little is withheld, you will generally owe tax when you file your tax return and may owe a penalty. If too much is withheld, you will generally be due a refund. Complete a new Form W-4 when changes to your personal or financial situation would change the entries on the form. For more information on withholding and when you must furnish a new Form W-4, see Pub. 505, Tax Withholding and Estimated Tax.</p>
                    <p style="margin: 4px 0; font-size: 10px; line-height: 12px;"><b>Exemption from withholding.</b> You may claim exemption from withholding for 2025 if you meet both of the following conditions: you had no federal income tax liability in 2024 and you expect to have no federal income tax liability in 2025. You had no federal income tax liability in 2024 if (1) your total tax on line 24 on your 2024 Form 1040 or 1040-SR is zero (or less than the sum of lines 27, 28, and 29), or (2) you were not required to file a return because your income was below the filing threshold for your correct filing status. If you claim exemption, you will have no income tax withheld from your paycheck and may owe taxes and penalties when you file your 2025 tax return. To claim exemption from withholding, certify that you meet both of the conditions above by writing "Exempt" on Form W-4 in the space below Step 4(c). Then, complete Steps 1(a), 1(b), and 5. Do not complete any other steps. You will need to submit a new Form W-4 by February 17, 2026.</p>
                    <p style="margin: 4px 0; font-size: 10px; line-height: 12px;"><b>Your privacy.</b> Steps 2(c) and 4(a) ask for information regarding income you received from sources other than the job associated with this Form W-4. If you have concerns with providing the information asked for in Step 2(c), you may choose Step 2(b) as an alternative; if you have concerns with providing the information asked for in Step 4(a), you may enter an additional amount you want withheld per pay period in Step 4(c) as an alternative.</p>
                    <p style="margin: 4px 0; font-size: 10px; line-height: 12px;"><b>When to use the estimator.</b> Consider using the estimator at <a href="https://www.irs.gov/W4App" target="_blank" style="color: #0066cc;">www.irs.gov/W4App</a> if you:</p>
                    <p style="margin: 2px 0; font-size: 10px; line-height: 12px;">1. Are submitting this form after the beginning of the year;</p>
                    <p style="margin: 2px 0; font-size: 10px; line-height: 12px;">2. Expect to work only part of the year;</p>
                    <p style="margin: 2px 0; font-size: 10px; line-height: 12px;">3. Have changes during the year in your marital status, number of jobs for you (and/or your spouse if married filing jointly), or number of dependents, or changes in your deductions or credits;</p>
                    <p style="margin: 2px 0; font-size: 10px; line-height: 12px;">4. Receive dividends, capital gains, social security, bonuses, or business income, or are subject to the Additional Medicare Tax or Net Investment Income Tax; or</p>
                    <p style="margin: 2px 0; font-size: 10px; line-height: 12px;">5. Prefer the most accurate withholding for multiple job situations.</p>
                    <p style="margin: 4px 0; font-size: 10px; line-height: 12px;"><b>TIP:</b> Have your most recent pay stub(s) from this year available when using the estimator to account for federal income tax that has already been withheld this year. At the beginning of next year, use the estimator again to recheck your withholding.</p>
                    <p style="margin: 4px 0; font-size: 10px; line-height: 12px;"><b>Self-employment.</b> Generally, you will owe both income and self-employment taxes on any self-employment income you receive separate from the wages you receive as an employee. If you want to pay these taxes through withholding from your wages, use the estimator at <a href="https://www.irs.gov/W4App" target="_blank" style="color: #0066cc;">www.irs.gov/W4App</a> to figure the amount to have withheld.</p>
                </td>
                <td style="padding-left: 20px; vertical-align: top;" width="50%">
                    <p style="margin: 4px 0; font-size: 10px; line-height: 12px;">Nonresident alien. If you're a nonresident alien, see Notice 1392, Supplemental Form W-4 Instructions for Nonresident Aliens, before completing this form.</p>
                    <div style="margin-top: 0; margin-bottom: 5px; font-size: 18px; font-weight: 600;">Specific Instructions</div>
                    <p style="margin: 4px 0; font-size: 10px; line-height: 12px;"><b>Step 1(c).</b> Check your anticipated filing status. This will determine the standard deduction and tax rates used to compute your withholding.</p>
                    <p style="margin: 4px 0; font-size: 10px; line-height: 12px;"><b>Step 2.</b> Use this step if you (1) have more than one job at the same time, or (2) are married filing jointly and you and your spouse both work. Submit a separate Form W-4 for each job.</p>
                    <p style="margin: 4px 0; font-size: 10px; line-height: 12px;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Option (a) most accurately calculates the additional tax you need to have withheld, while option (b) does so with a little less accuracy.</p>
                    <p style="margin: 4px 0; font-size: 10px; line-height: 12px;">&nbsp;&nbsp;&nbsp;&nbsp;Instead, if you (and your spouse) have a total of only two jobs, you may check the box in option (c). The box must also be checked on the Form W-4 for the other job. If the box is checked, the standard deduction and tax brackets will be cut in half for each job to calculate withholding. This option is accurate for jobs with similar pay; otherwise, more tax than necessary may be withheld, and this extra amount will be larger the greater the difference in pay is between the two jobs.</p>
                    <p style="margin: 4px 0; font-size: 10px; line-height: 12px;">Multiple jobs. Complete Steps 3 through 4(b) on only one Form W-4. Withholding will be most accurate if you do this on the Form W-4 for the highest paying job.</p>
                    <p style="margin: 4px 0; font-size: 10px; line-height: 12px;"><b>Step 3.</b> This step provides instructions for determining the amount of the child tax credit and the credit for other dependents that you may be able to claim when you file your tax return. To qualify for the child tax credit, the child must be under age 17 as of December 31, must be your dependent who generally lives with you for more than half the year, and must have the required social security number. You may be able to claim a credit for other dependents for whom a child tax credit can't be claimed, such as an older child or a qualifying relative. For additional eligibility requirements for these credits, see Pub. 501, Dependents, Standard Deduction, and Filing Information. You can also include <b>other tax credits</b> for which you are eligible in this step, such as the foreign tax credit and the education tax credits. To do so, add an estimate of the amount for the year to your credits for dependents and enter the total amount in Step 3. Including these credits will increase your paycheck and reduce the amount of any refund you may receive when you file your tax return.</p>
                    <p style="margin: 4px 0; font-size: 10px; line-height: 12px;"><b>Step 4 (optional).</b></p>
                    <p style="margin: 2px 0; font-size: 10px; line-height: 12px;">&nbsp;&nbsp;&nbsp;&nbsp;<b>Step 4(a).</b> Enter in this step the total of your other estimated income for the year, if any. You shouldn't include income from any jobs or self-employment. If you complete Step 4(a), you likely won't have to make estimated tax payments for that income. If you prefer to pay estimated tax rather than having tax on other income withheld from your paycheck, see Form 1040-ES, Estimated Tax for Individuals.</p>
                    <p style="margin: 2px 0; font-size: 10px; line-height: 12px;">&nbsp;&nbsp;&nbsp;&nbsp;<b>Step 4(b).</b> Enter in this step the amount from the Deductions Worksheet, line 5, if you expect to claim deductions other than the basic standard deduction on your 2025 tax return and want to reduce your withholding to account for these deductions. This includes both itemized deductions and other deductions such as for student loan interest and IRAs.</p>
                    <p style="margin: 2px 0; font-size: 10px; line-height: 12px;">&nbsp;&nbsp;&nbsp;&nbsp;<b>Step 4(c).</b> Enter in this step any additional tax you want withheld from your pay each pay period, including any amounts from the Multiple Jobs Worksheet, line 4. Entering an amount here will reduce your paycheck and will either increase your refund or reduce any amount of tax that you owe.</p>
                </td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 10px; font-size: 11px;">
        <label><b>For the Deductions Worksheet, Multiple Jobs Worksheet, and Privacy Act and Paperwork Reduction Act Notice, see page 3.</b></label>
    </div>
    <div style="margin-top: 3px; font-size: 11px;">
        <label style="margin-right: 130px;">Cat. No. 10220Q</label>
        <label>Form <b>W-4</b> (2025)</label>
    </div>
</div>

<div class="inner w4-page-3" style="page-break-after: always;">
    <div style="font-size: 10px;display: inline-block;width: 100%;">
        <label style="float: left;">Form W-4 (2025)</label>
        <label style="float: right;">Page <b>3</b></label>
    </div>

    <div
        style="font-size: 14px;line-height: 16px; text-align: center; margin-top: 0;border-bottom: 2px solid;border-top: 2px solid;padding-bottom: 3px;margin-bottom: 5px;">
        <b>Step 2(b)—Multiple Jobs Worksheet</b> <i>(Keep for your records.)</i></div>

    <div>
        <p>If you choose the option in Step 2(b) on Form W-4, complete this worksheet (which calculates the total
            extra tax for all jobs) on <b>only ONE</b> Form W-4. Withholding will be most accurate if you complete
            the worksheet and enter the result on the Form W-4 for the highest paying job. To be accurate, submit a
            new Form W-4 for all other jobs if you have not updated your withholding since 2019.</p>
        <p><b>Note:</b> If more than one job has annual wages of more than $120,000 or there are more than three
            jobs, see Pub. 505 for additional tables; or, you can use the online withholding estimator at
            www.irs.gov/W4App.</p>
    </div>

    <table style="width: 100%;font-size: 10px;line-height: 10px;">
        <tbody>
            <tr>
                <td style="padding: 5px 8px;padding-left: 0;" width="80%"><b>1. Two jobs.</b> If you have two jobs
                    or you're married filing jointly and you and your spouse each have one job, find the amount from
                    the appropriate table on page 4. Using the "Higher Paying Job" row and the "Lower Paying Job"
                    column, find the value at the intersection of the two household salaries and enter that value on
                    line 1. Then, <b>skip</b> to line 3</td>
                <td style="text-align: center;vertical-align: bottom;padding-bottom: 5px;"><b>1 $</b><span class="w4-field" style="display:inline-block;height: 10px;width: 80px;border-bottom: 1px solid;line-height: 26px;"><p>{{ $w4?->worksheet_line1 ?? '' }}</p></span>
                </td>
            </tr>
        </tbody>
    </table>
    <div>
        <p><b>2. Three jobs.</b> If you and/or your spouse have three jobs at the same time, complete lines 2a, 2b,
            and 2c below. Otherwise, skip to line 3.</p>
    </div>

    <table style="width: 100%;font-size: 10px;line-height: 10px;">
        <tbody>
            <tr>
                <td style="padding: 5px 8px;padding-left: 30px;" width="80%"><b>a</b> Find the amount from the
                    appropriate table on page 4 using the annual wages from the highest paying job in the "Higher
                    Paying Job" row and the annual wages for your next highest paying job in the "Lower Paying Job"
                    column. Find the value at the intersection of the two household salaries and enter that value on
                    line 2a . . .</td>
                <td style="text-align: center;vertical-align: bottom;padding-bottom: 5px;"><b>2a $</b><span class="w4-field" style="display:inline-block;height: 10px;width: 80px;border-bottom: 1px solid;line-height: 26px;"><p>{{ $w4?->worksheet_line2a ?? '' }}</p></span>
                </td>
            </tr>
            <tr>
                <td style="padding: 5px 8px;padding-left: 30px;" width="80%"><b>b</b> Add the annual wages of the
                    two highest paying jobs from line 2a together and use the total as the wages in the "Higher
                    Paying Job" row and use the annual wages for your third job in the "Lower Paying Job" column to
                    find the amount from the appropriate table on page 4 and enter this amount on line 2b . . . . .
                    . . . . . . . . . . . . .</td>
                <td style="text-align: center;vertical-align: bottom;padding-bottom: 5px;"><b>2b $</b><span class="w4-field" style="display:inline-block;height: 10px;width: 80px;border-bottom: 1px solid;line-height: 26px;"><p>{{ $w4?->worksheet_line2b ?? '' }}</p></span>
                </td>
            </tr>
            <tr>
                <td style="padding: 5px 8px;padding-left: 30px;" width="80%"><b>c</b> Add the amounts from lines 2a
                    and 2b and enter the result on line 2c . . . . . . . . . . . . .</td>
                <td style="text-align: center;vertical-align: bottom;padding-bottom: 5px;"><b>2c $</b><span class="w4-field" style="display:inline-block;height: 10px;width: 80px;border-bottom: 1px solid;line-height: 26px;"><p>{{ $w4?->worksheet_line2c ?? '' }}</p></span>
                </td>
            </tr>
        </tbody>
    </table>

    <table style="width: 100%;font-size: 10px;line-height: 10px;">
        <tbody>
            <tr>
                <td style="padding: 5px 8px;padding-left: 0;" width="80%"><b>3</b> Enter the number of pay periods
                    per year for the highest paying job. For example, if that job pays weekly, enter 52; if it pays
                    every other week, enter 26; if it pays monthly, enter 12, etc. . . . . . . . . . . . . . . . . .
                    . .</td>
                <td style="text-align: center;vertical-align: bottom;padding-bottom: 5px;"><b>3</b> <span class="w4-field" style="display:inline-block;height: 10px;width: 80px;border-bottom: 1px solid;line-height: 26px;"><p>{{ $w4?->worksheet_line3 ?? '' }}</p></span>
                </td>
            </tr>
            <tr>
                <td style="padding: 5px 8px;padding-left: 0;" width="80%"><b>4 Divide</b> the annual amount on line
                    1 or line 2c by the number of pay periods on line 3. Enter this amount here and in <b>Step
                        4(c)</b> of Form W-4 for the highest paying job (along with any other additional amount you
                    want withheld)</td>
                <td style="text-align: center;vertical-align: bottom;padding-bottom: 5px;"><b>4 $</b><span class="w4-field" style="display:inline-block;height: 10px;width: 80px;border-bottom: 1px solid;line-height: 26px;"><p>{{ $w4?->worksheet_line4 ?? '' }}</p></span>
                </td>
            </tr>
        </tbody>
    </table>

    <div
        style="font-size: 14px;line-height: 16px; text-align: center; margin-top: 0;border-bottom: 2px solid;border-top: 2px solid;padding-bottom: 3px;margin-bottom: 5px;">
        <b>Step 4(b)—Deductions Worksheet</b> <i>(Keep for your records.)</i>
    </div>

    <table style="width: 100%;font-size: 10px;line-height: 10px;">
        <tbody>
            <tr>
                <td style="padding: 5px 8px;padding-left: 0;" width="80%"><b>1</b> Enter an estimate of your 2025
                    itemized deductions (from Schedule A (Form 1040)). Such deductions may include qualifying home
                    mortgage interest, charitable contributions, state and local taxes (up to $10,000), and medical
                    expenses in excess of 7.5% of your income . . . . . . . . . . . . . . . .</td>
                <td style="text-align: center;vertical-align: bottom;padding-bottom: 5px;"><b>1 $</b><span class="w4-field" style="display:inline-block;height: 10px;width: 80px;border-bottom: 1px solid;line-height: 26px;"><p>{{ $w4?->worksheet_ded1 ?? '' }}</p></span>
                </td>
            </tr>
            <tr>
                <td style="padding: 5px 8px;padding-left: 0;" width="80%"><b>2</b> Enter
                    <ul style="margin-bottom: 0;">
                        <li>$30,000 if you're married filing jointly or a qualifying surviving spouse</li>
                        <li>$22,500 if you're head of household</li>
                        <li>$15,000 if you're single or married filing separately</li>
                    </ul>
                </td>
                <td style="text-align: center;vertical-align: bottom;padding-bottom: 5px;"><b>2 $</b><span class="w4-field" style="display:inline-block;height: 10px;width: 80px;border-bottom: 1px solid;line-height: 26px;"><p>{{ $w4?->worksheet_ded2 ?? '' }}</p></span>
                </td>
            </tr>
            <tr>
                <td style="padding: 5px 8px;padding-left: 0;" width="80%"><b>3</b> If line 1 is greater than line 2,
                    subtract line 2 from line 1 and enter the result here. If line 2 is greater than line 1, enter
                    "-0-" . . . . . . . . . . . .
                </td>
                <td style="text-align: center;vertical-align: bottom;padding-bottom: 5px;"><b>3 $</b><span class="w4-field" style="display:inline-block;height: 10px;width: 80px;border-bottom: 1px solid;line-height: 26px;"><p>{{ $w4?->worksheet_ded3 ?? '' }}</p></span>
                </td>
            </tr>
            <tr>
                <td style="padding: 5px 8px;padding-left: 0;" width="80%"><b>4</b> Enter an estimate of your student
                    loan interest, deductible IRA contributions, and certain other adjustments (from Part II of
                    Schedule 1 (Form 1040)). See Pub. 505 for more information . . . . . . . . . . . .</td>
                <td style="text-align: center;vertical-align: bottom;padding-bottom: 5px;"><b>4 $</b><span class="w4-field" style="display:inline-block;height: 10px;width: 80px;border-bottom: 1px solid;line-height: 26px;"><p>{{ $w4?->worksheet_ded4 ?? '' }}</p></span>
                </td>
            </tr>
            <tr>
                <td style="padding: 5px 8px;padding-left: 0;" width="80%"><b>5 Add</b> lines 3 and 4. Enter the
                    result here and in <b>Step 4(b)</b> of Form W-4 . . . . . . . . . . . .
                </td>
                <td style="text-align: center;vertical-align: bottom;padding-bottom: 5px;"><b>5 $</b><span class="w4-field" style="display:inline-block;height: 10px;width: 80px;border-bottom: 1px solid;line-height: 26px;"><p>{{ $w4?->worksheet_ded5 ?? '' }}</p></span>
                </td>
            </tr>
        </tbody>
    </table>
    <hr style="border-color: #000;border-width: medium;opacity: 1;">

    <table style="width: 100%;font-size: 10px;line-height: 10px;">
        <tbody>
            <tr>
                <td style="padding: 5px 8px;padding-left: 0;" width="50%"><b>Privacy Act and Paperwork Reduction Act
                        Notice.</b> We ask for the information on this form to carry out the Internal Revenue laws
                    of the United States. Internal Revenue Code sections 3402(f)(2) and 6109 and their regulations
                    require you to provide this information; your employer uses it to determine your federal income
                    tax withholding. Failure to provide a properly completed form will result in your being treated
                    as a single person with no other entries on the form; providing fraudulent information may
                    subject you to penalties. Routine uses of this information include giving it to the Department
                    of Justice for civil and criminal litigation; to cities, states, the District of Columbia, and
                    U.S. commonwealths and territories for use in administering their tax laws; and to the
                    Department of Health and Human Services for use in the National Directory of New Hires. We may
                    also disclose this information to other countries under a tax treaty, to federal and state
                    agencies to enforce federal nontax criminal laws, or to federal law enforcement and intelligence
                    agencies to combat terrorism.</td>
                <td style="padding: 5px 8px;padding-left: 0;vertical-align: baseline;" width="50%">You are not
                    required to provide the information requested on a form that is subject to the Paperwork
                    Reduction Act unless the form displays a valid OMB control number. Books or records relating to
                    a form or its instructions must be retained as long as their contents may become material in the
                    administration of any Internal Revenue law. Generally, tax returns and return information are
                    confidential, as required by Code section 6103. The average time and expenses required to
                    complete and file this form will vary depending on individual circumstances. For estimated
                    averages, see the instructions for your income tax return. If you have suggestions for making
                    this form simpler, we would be happy to hear from you. See the instructions for your income tax
                    return.</td>
            </tr>
        </tbody>
    </table>
</div>

<div class="inner w4-page-4">
    <div style="font-size: 10px;line-height: 10px;display: inline-block;width: 100%;">
        <label style="float: left;">Form W-4 (2025)</label>
        <label style="float: right;">Page <b>4</b></label>
    </div>

    <div
        style="font-size: 14px;line-height: 16px; text-align: center; margin-top: 0;padding-bottom: 3px;border-top: 2px solid;">
        <b>Married Filing Jointly or Qualifying Surviving Spouse</b></div>

    <table border="1"
        style="border-collapse: collapse;border-top: none;width: 100%;font-size: 10px;line-height: 10px;text-align: center;">
        <tbody>
            <tr>
                <td rowspan="2" style="padding: 5px 8px;" width="15%"><b>Higher Paying Job Annual Taxable Wage &
                        Salary</b></td>
                <td colspan="12" style="text-align: center;padding: 5px 8px;border-left: 1px solid #000;"><b>Lower
                        Paying Job Annual Taxable Wage & Salary</b></td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 3px;">$0 - 9,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$10,000 - 19,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$20,000 - 29,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$30,000 - 39,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$40,000 - 49,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$50,000 - 59,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$60,000 - 69,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$70,000 - 79,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$80,000 - 89,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$90,000 - 99,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$100,000- 109,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$110,000- 120,000</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$0 - 9,999</div>
                    <div>$10,000 - 19,999</div>
                    <div>$20,000 - 29,999</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$0</div>
                    <div>0</div>
                    <div>700</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$0</div>
                    <div>700</div>
                    <div>1,700</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$700</div>
                    <div>1,700</div>
                    <div>2,760</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$850</div>
                    <div>1,910</div>
                    <div>3,110</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$910</div>
                    <div>2,110</div>
                    <div>3,310</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$1,020</div>
                    <div>2,220</div>
                    <div>3,420</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$1,020</div>
                    <div>2,220</div>
                    <div>3,420</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$1,020</div>
                    <div>2,220</div>
                    <div>3,420</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$1,020</div>
                    <div>2,220</div>
                    <div>3,420</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$1,020</div>
                    <div>2,220</div>
                    <div>3,420</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$1,020</div>
                    <div>2,220</div>
                    <div>4,420</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$1,020</div>
                    <div>3,220</div>
                    <div>5,420</div>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$30,000 - 39,999</div>
                    <div>$40,000 - 49,999</div>
                    <div>$50,000 - 59,999</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>850</div>
                    <div>910</div>
                    <div>1,020</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>1,910</div>
                    <div>2,110</div>
                    <div>2,220</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>3,110</div>
                    <div>3,310</div>
                    <div>3,420</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>3,460</div>
                    <div>3,660</div>
                    <div>3,770</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>3,660</div>
                    <div>3,860</div>
                    <div>3,970</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>3,770</div>
                    <div>3,970</div>
                    <div>4,080</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>3,770</div>
                    <div>3,970</div>
                    <div>4,080</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>3,770</div>
                    <div>3,970</div>
                    <div>4,080</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>3,770</div>
                    <div>4,970</div>
                    <div>5,080</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>4,770</div>
                    <div>5,970</div>
                    <div>6,080</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>5,770</div>
                    <div>6,970</div>
                    <div>7,080</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>6,770</div>
                    <div>7,970</div>
                    <div>9,080</div>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$60,000 - 69,999</div>
                    <div>$70,000 - 79,999</div>
                    <div>$80,000 - 99,999</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>1,020</div>
                    <div>1,020</div>
                    <div>1,020</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>2,220</div>
                    <div>2,220</div>
                    <div>2,220</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>3,420</div>
                    <div>3,420</div>
                    <div>4,620</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>3,770</div>
                    <div>3,770</div>
                    <div>5,820</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>3,970</div>
                    <div>3,970</div>
                    <div>6,330</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>4,080</div>
                    <div>5,080</div>
                    <div>7,930</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>5,080</div>
                    <div>6,080</div>
                    <div>8,830</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>6,080</div>
                    <div>7,080</div>
                    <div>9,930</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>7,080</div>
                    <div>8,080</div>
                    <div>10,930</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>8,080</div>
                    <div>9,080</div>
                    <div>11,930</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>9,080</div>
                    <div>10,080</div>
                    <div>11,930</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>10,080</div>
                    <div>11,080</div>
                    <div>12,930</div>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$100,000 - 149,999</div>
                    <div>$150,000 - 239,999</div>
                    <div>$240,000 - 259,999</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>1,870</div>
                    <div>1,870</div>
                    <div>2,040</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>4,070</div>
                    <div>4,240</div>
                    <div>4,440</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>6,270</div>
                    <div>6,840</div>
                    <div>6,840</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>7,620</div>
                    <div>8,190</div>
                    <div>8,390</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>8,820</div>
                    <div>9,590</div>
                    <div>9,790</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>9,930</div>
                    <div>10,890</div>
                    <div>11,100</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>10,930</div>
                    <div>12,090</div>
                    <div>12,300</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>11,930</div>
                    <div>13,290</div>
                    <div>13,500</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>13,290</div>
                    <div>14,490</div>
                    <div>14,700</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>14,010</div>
                    <div>15,690</div>
                    <div>15,900</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>15,210</div>
                    <div>16,890</div>
                    <div>17,100</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>16,410</div>
                    <div>18,090</div>
                    <div>18,300</div>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$260,000 - 279,999</div>
                    <div>$280,000 - 299,999</div>
                    <div>$300,000 - 319,999</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>2,040</div>
                    <div>2,040</div>
                    <div>2,040</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>4,440</div>
                    <div>4,440</div>
                    <div>4,440</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>6,840</div>
                    <div>6,840</div>
                    <div>6,840</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>8,390</div>
                    <div>8,390</div>
                    <div>8,390</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>9,790</div>
                    <div>9,790</div>
                    <div>9,790</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>11,100</div>
                    <div>11,100</div>
                    <div>11,100</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>12,300</div>
                    <div>12,300</div>
                    <div>12,300</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>13,500</div>
                    <div>13,500</div>
                    <div>13,500</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>14,700</div>
                    <div>14,700</div>
                    <div>14,700</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>15,900</div>
                    <div>15,900</div>
                    <div>15,900</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>17,100</div>
                    <div>17,100</div>
                    <div>17,170</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>18,300</div>
                    <div>18,300</div>
                    <div>19,170</div>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$320,000 - 364,999</div>
                    <div>$365,000 - 524,999</div>
                    <div>$525,000 and over</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>2,040</div>
                    <div>2,790</div>
                    <div>3,140</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>4,440</div>
                    <div>6,290</div>
                    <div>6,840</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>6,840</div>
                    <div>9,270</div>
                    <div>10,540</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>8,390</div>
                    <div>12,440</div>
                    <div>13,390</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>9,790</div>
                    <div>14,940</div>
                    <div>16,090</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>11,100</div>
                    <div>17,350</div>
                    <div>18,700</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>12,470</div>
                    <div>19,650</div>
                    <div>21,200</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>14,470</div>
                    <div>21,950</div>
                    <div>23,700</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>16,470</div>
                    <div>24,250</div>
                    <div>26,200</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>18,470</div>
                    <div>26,550</div>
                    <div>28,700</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>20,470</div>
                    <div>28,850</div>
                    <div>31,200</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>22,470</div>
                    <div>31,150</div>
                    <div>33,700</div>
                </td>
            </tr>
        </tbody>
    </table>

    <div style="font-size: 14px;line-height: 16px; text-align: center; margin-top: 10px;padding-bottom: 3px;">
        <b>Single or Married Filing Separately</b></div>

    <table border="1"
        style="border-collapse: collapse;border-top: none;width: 100%;font-size: 10px;line-height: 10px;text-align: center;">
        <tbody>
            <tr>
                <td rowspan="2" style="padding: 5px 8px;" width="15%"><b>Single or Married Filing Separately</b>
                </td>
                <td colspan="12" style="text-align: center;padding: 5px 8px;border-left: 1px solid #000;"><b>Lower
                        Paying Job Annual Taxable Wage & Salary</b></td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 3px;">$0 - 9,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$10,000 - 19,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$20,000 - 29,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$30,000 - 39,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$40,000 - 49,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$50,000 - 59,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$60,000 - 69,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$70,000 - 79,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$80,000 - 89,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$90,000 - 99,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$100,000- 109,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$110,000- 120,000</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$0 - 9,999</div>
                    <div>$10,000 - 19,999</div>
                    <div>$20,000 - 29,999</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>200</div>
                    <div>850</div>
                    <div>1,020</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>850</div>
                    <div>1,700</div>
                    <div>1,870</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>1,020</div>
                    <div>1,870</div>
                    <div>2,040</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>1,020</div>
                    <div>1,870</div>
                    <div>2,390</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>1,020</div>
                    <div>2,220</div>
                    <div>3,390</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>1,020</div>
                    <div>3,220</div>
                    <div>4,390</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>1,370</div>
                    <div>3,720</div>
                    <div>4,890</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>1,870</div>
                    <div>3,720</div>
                    <div>4,890</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>1,870</div>
                    <div>3,720</div>
                    <div>5,060</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>1,870</div>
                    <div>3,720</div>
                    <div>5,260</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>1,870</div>
                    <div>3,890</div>
                    <div>5,460</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>2,040</div>
                    <div>4,090</div>
                    <div>5,460</div>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$30,000 - 39,999</div>
                    <div>$40,000 - 59,999</div>
                    <div>$60,000 - 79,999</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>1,020</div>
                    <div>1,220</div>
                    <div>1,870</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>1,870</div>
                    <div>3,070</div>
                    <div>3,720</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>2,390</div>
                    <div>4,240</div>
                    <div>4,890</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>3,390</div>
                    <div>5,240</div>
                    <div>5,890</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>4,390</div>
                    <div>6,240</div>
                    <div>7,030</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>5,390</div>
                    <div>7,240</div>
                    <div>8,030</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>5,890</div>
                    <div>7,880</div>
                    <div>8,930</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>5,890</div>
                    <div>8,080</div>
                    <div>9,330</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>6,260</div>
                    <div>8,280</div>
                    <div>9,530</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>6,460</div>
                    <div>8,480</div>
                    <div>9,730</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>6,660</div>
                    <div>8,680</div>
                    <div>9,930</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>6,660</div>
                    <div>8,880</div>
                    <div>9,930</div>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$80,000 - 99,999</div>
                    <div>$100,000 - 124,999</div>
                    <div>$125,000 - 149,999</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>1,870</div>
                    <div>2,040</div>
                    <div>2,040</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>3,720</div>
                    <div>4,090</div>
                    <div>4,090</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>5,030</div>
                    <div>5,460</div>
                    <div>5,460</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>6,230</div>
                    <div>6,660</div>
                    <div>6,660</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>7,430</div>
                    <div>7,860</div>
                    <div>7,860</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>8,630</div>
                    <div>9,060</div>
                    <div>9,060</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>9,330</div>
                    <div>9,760</div>
                    <div>9,950</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>9,530</div>
                    <div>9,960</div>
                    <div>10,950</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>9,930</div>
                    <div>10,160</div>
                    <div>11,950</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>10,130</div>
                    <div>10,390</div>
                    <div>12,950</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>10,580</div>
                    <div>10,950</div>
                    <div>13,950</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>10,580</div>
                    <div>12,950</div>
                    <div>14,950</div>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$150,000 - 174,999</div>
                    <div>$175,000 - 199,999</div>
                    <div>$200,000 - 249,999</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>2,040</div>
                    <div>2,040</div>
                    <div>2,720</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>4,090</div>
                    <div>4,290</div>
                    <div>5,570</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>5,460</div>
                    <div>6,450</div>
                    <div>7,900</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>6,660</div>
                    <div>8,450</div>
                    <div>10,200</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>8,450</div>
                    <div>10,450</div>
                    <div>12,500</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>10,450</div>
                    <div>12,450</div>
                    <div>14,800</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>11,950</div>
                    <div>13,950</div>
                    <div>16,600</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>12,950</div>
                    <div>15,230</div>
                    <div>17,900</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>13,950</div>
                    <div>16,530</div>
                    <div>19,200</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>15,080</div>
                    <div>17,830</div>
                    <div>20,500</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>16,380</div>
                    <div>19,130</div>
                    <div>21,800</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>17,680</div>
                    <div>20,430</div>
                    <div>23,100</div>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$250,000 - 399,999</div>
                    <div>$400,000 - 449,999</div>
                    <div>$450,000 and over</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>2,970</div>
                    <div>2,970</div>
                    <div>3,140</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>6,120</div>
                    <div>6,120</div>
                    <div>6,490</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>8,590</div>
                    <div>8,590</div>
                    <div>9,160</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>10,890</div>
                    <div>10,890</div>
                    <div>11,660</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>13,190</div>
                    <div>13,190</div>
                    <div>14,160</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>15,490</div>
                    <div>15,490</div>
                    <div>16,660</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>17,290</div>
                    <div>17,290</div>
                    <div>18,660</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>18,590</div>
                    <div>18,590</div>
                    <div>20,160</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>19,890</div>
                    <div>19,890</div>
                    <div>21,160</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>21,190</div>
                    <div>21,190</div>
                    <div>23,160</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>22,490</div>
                    <div>22,490</div>
                    <div>24,660</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>23,790</div>
                    <div>23,790</div>
                    <div>26,160</div>
                </td>
            </tr>
        </tbody>
    </table>

    <div style="font-size: 14px;line-height: 16px; text-align: center; margin-top: 10px;padding-bottom: 3px;">
        <b>Head of Household</b></div>

    <table border="1"
        style="border-collapse: collapse;border-top: none;width: 100%;font-size: 10px;line-height: 10px;text-align: center;">
        <tbody>
            <tr>
                <td rowspan="2" style="padding: 5px 8px;" width="15%"><b>Head of Household</b></td>
                <td colspan="12" style="text-align: center;padding: 5px 8px;border-left: 1px solid #000;"><b>Lower
                        Paying Job Annual Taxable Wage & Salary</b></td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 3px;">$0 - 9,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$10,000 - 19,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$20,000 - 29,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$30,000 - 39,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$40,000 - 49,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$50,000 - 59,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$60,000 - 69,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$70,000 - 79,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$80,000 - 89,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$90,000 - 99,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$100,000- 109,999</td>
                <td style="border: 1px solid #000;padding: 3px;">$110,000- 120,000</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$0 - 9,999</div>
                    <div>$10,000 - 19,999</div>
                    <div>$20,000 - 29,999</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$0</div>
                    <div>450</div>
                    <div>850</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$450</div>
                    <div>1,450</div>
                    <div>2,000</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$850</div>
                    <div>2,000</div>
                    <div>2,600</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$1,000</div>
                    <div>2,200</div>
                    <div>2,800</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$1,020</div>
                    <div>2,220</div>
                    <div>2,820</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$1,020</div>
                    <div>2,220</div>
                    <div>2,820</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$1,020</div>
                    <div>2,220</div>
                    <div>3,780</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$1,020</div>
                    <div>3,180</div>
                    <div>4,780</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$1,870</div>
                    <div>4,070</div>
                    <div>5,670</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$1,870</div>
                    <div>4,070</div>
                    <div>5,690</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$1,870</div>
                    <div>4,090</div>
                    <div>5,890</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$1,890</div>
                    <div>4,290</div>
                    <div>6,090</div>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$30,000 - 39,999</div>
                    <div>$40,000 - 59,999</div>
                    <div>$60,000 - 79,999</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>1,000</div>
                    <div>1,020</div>
                    <div>1,020</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>2,200</div>
                    <div>2,220</div>
                    <div>3,030</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>2,800</div>
                    <div>2,820</div>
                    <div>4,630</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>3,000</div>
                    <div>3,830</div>
                    <div>5,830</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>3,020</div>
                    <div>4,850</div>
                    <div>6,850</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>3,980</div>
                    <div>5,850</div>
                    <div>8,050</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>4,980</div>
                    <div>6,850</div>
                    <div>9,250</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>5,980</div>
                    <div>8,050</div>
                    <div>10,450</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>6,890</div>
                    <div>9,130</div>
                    <div>11,530</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>7,090</div>
                    <div>9,330</div>
                    <div>11,730</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>7,290</div>
                    <div>9,530</div>
                    <div>11,930</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>7,490</div>
                    <div>9,730</div>
                    <div>12,130</div>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$80,000 - 99,999</div>
                    <div>$100,000 - 124,999</div>
                    <div>$125,000 - 149,999</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>1,870</div>
                    <div>1,950</div>
                    <div>2,040</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>4,070</div>
                    <div>4,350</div>
                    <div>4,440</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>5,670</div>
                    <div>6,150</div>
                    <div>6,240</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>7,060</div>
                    <div>7,550</div>
                    <div>7,640</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>8,280</div>
                    <div>8,770</div>
                    <div>8,860</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>9,480</div>
                    <div>9,970</div>
                    <div>10,060</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>10,680</div>
                    <div>11,170</div>
                    <div>11,260</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>11,880</div>
                    <div>12,370</div>
                    <div>12,860</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>12,970</div>
                    <div>13,450</div>
                    <div>14,740</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>13,170</div>
                    <div>13,650</div>
                    <div>15,740</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>13,370</div>
                    <div>14,650</div>
                    <div>16,740</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>13,570</div>
                    <div>15,650</div>
                    <div>17,740</div>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$150,000 - 174,999</div>
                    <div>$175,000 - 199,999</div>
                    <div>$200,000 - 249,999</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>2,040</div>
                    <div>2,040</div>
                    <div>2,720</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>4,440</div>
                    <div>4,440</div>
                    <div>5,920</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>6,240</div>
                    <div>6,640</div>
                    <div>8,520</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>7,640</div>
                    <div>8,840</div>
                    <div>10,960</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>8,860</div>
                    <div>10,860</div>
                    <div>13,280</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>10,860</div>
                    <div>12,860</div>
                    <div>15,580</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>12,860</div>
                    <div>14,860</div>
                    <div>17,880</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>14,860</div>
                    <div>16,910</div>
                    <div>20,180</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>16,740</div>
                    <div>19,090</div>
                    <div>22,360</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>17,740</div>
                    <div>20,390</div>
                    <div>23,660</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>18,940</div>
                    <div>21,690</div>
                    <div>24,960</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>20,240</div>
                    <div>22,990</div>
                    <div>26,260</div>
                </td>
            </tr>
            <tr>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>$250,000 - 449,999</div>
                    <div>$450,000 and over</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>2,970</div>
                    <div>3,140</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>6,470</div>
                    <div>6,840</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>9,370</div>
                    <div>9,940</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>11,870</div>
                    <div>12,640</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>14,190</div>
                    <div>15,160</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>16,490</div>
                    <div>17,660</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>18,790</div>
                    <div>20,160</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>21,090</div>
                    <div>22,660</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>23,280</div>
                    <div>25,050</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>24,580</div>
                    <div>26,550</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>25,880</div>
                    <div>28,050</div>
                </td>
                <td style="border: 1px solid #000;padding: 3px;">
                    <div>27,180</div>
                    <div>29,550</div>
                </td>
            </tr>
        </tbody>
    </table>
</div>

</body>

</html>
