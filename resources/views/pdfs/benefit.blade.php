<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Benefit Election Form</title>
    <style>
        @page { margin: 10px 20px; }
        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.35;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 5px 6px;
            vertical-align: top;
        }
        .section-title {
            background: #f0f0f0;
            font-weight: bold;
            font-size: 12px;
            padding: 6px;
        }
        .small {
            font-size: 10px;
            color: #333;
        }
        .header-note {
            color: #c00;
            font-weight: bold;
            text-align: right;
            vertical-align: middle;
        }
        .noborder, .noborder td, .noborder th { border: 0 !important; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .benefit-cb {
            display: inline-block;
            width: 11px;
            height: 11px;
            border: 1px solid #000;
            text-align: center;
            line-height: 11px;
            font-size: 10px;
            font-weight: bold;
            vertical-align: middle;
        }
        .amount-cell { white-space: nowrap; }
        .auth-text {
            text-align: justify;
            margin: 8px 0;
            font-size: 10px;
            line-height: 1.4;
        }
        .signature-line {
            border-bottom: 1px solid #000;
            min-height: 18px;
            margin-top: 4px;
        }
    </style>
</head>
<body>
@php
    use App\Support\BenefitElection;

    $onboarding = $onboarding ?? null;
    $benefitEnrollment = $benefitEnrollment ?? null;
    $employee = $employee ?? $onboarding?->employee ?? null;
    $formatDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('m-d-Y') : '';
    $formatMoney = fn($v) => $v !== null && $v !== '' ? '$' . number_format((float) $v, 2) : '';

    $firstName = $onboarding?->applicant_first_name ?? $employee?->first_name ?? '';
    $lastName = $onboarding?->applicant_last_name ?? $employee?->last_name ?? '';
    $middleInitial = $onboarding?->applicant_middle_initial ?? $employee?->middle_name ?? '';
    $dob = $onboarding?->dob ?? $employee?->dob ?? '';
    $ssn = $employee?->ssn ?? '';
    $address = $onboarding?->applicant_address ?? $employee?->street ?? '';
    $city = $onboarding?->city ?? $employee?->city ?? '';
    $employeeState = $onboarding?->state ?? $employee?->state ?? '';
    $zip = $onboarding?->zip_code ?? $employee?->zip ?? '';
    $email = $onboarding?->applicant_email ?? $employee?->email ?? '';
    $gender = $benefitEnrollment?->gender ?? '';
    $salary = $benefitEnrollment?->salary ?? '';
    $hireDate = $benefitEnrollment?->hire_date ?? $onboarding?->doj ?? '';
    $employeeSign = $benefitEnrollment?->employee_sign ?? '';
    $dependents = $benefitEnrollment?->dependents ?? collect();

    $stateCode = BenefitElection::resolveStateCode(
        $state ?? BenefitElection::resolveCompanyStateName($onboarding, $employee) ?? $employeeState ?? ''
    );
    $stateName = BenefitElection::stateDisplayName($stateCode);
    $benefitElection = BenefitElection::resolvedPlanForState($stateCode) ?? [];

    $medicalPlan = $benefitEnrollment?->medical_plan ?? '';
    $dentalPlan = $benefitEnrollment?->dental ?? '';
    $visionPlan = $benefitEnrollment?->vision ?? '';
    $lifeOption = $benefitEnrollment?->life_option ?? '';
    $taxMethod = $benefitEnrollment?->tax_method ?? '';

    $cb = fn(bool $checked) => '<span class="benefit-cb">' . ($checked ? '&#10003;' : '') . '</span>';

    $healthOp1 = $benefitElection['health_care_plan']['op1'] ?? [];
    $healthOp2 = $benefitElection['health_care_plan']['op2'] ?? [];
    $dentalRates = $benefitElection['dental_plan'] ?? [];
    $visionRates = $benefitElection['vision_plan'] ?? [];

    $medicalRows = [
        ['label' => 'Employee Only', 'op1' => 'option1_employee', 'op2' => 'option2_employee', 'rate' => 'employee_only'],
        ['label' => 'Employee/Child(ren)', 'op1' => 'option1_child', 'op2' => 'option2_child', 'rate' => 'employee_child'],
        ['label' => 'Employee/Spouse', 'op1' => 'option1_spouse', 'op2' => 'option2_spouse', 'rate' => 'employee_spouse'],
        ['label' => 'Employee/Family', 'op1' => 'option1_family', 'op2' => 'option2_family', 'rate' => 'employee_family'],
    ];
@endphp

<table class="noborder">
    <tr>
        <td><b>{{ $stateName }}</b></td>
        <td class="header-note">Mandatory to complete at time of hire</td>
    </tr>
    <tr>
        <td colspan="2"><b>Plan Year {{ $benefitElection['date'] ?? '' }} Benefit Election Form</b></td>
    </tr>
</table>

{{-- A. General Employee Information --}}
<table>
    <tr>
        <td colspan="5" class="section-title">A. General Employee Information</td>
    </tr>
    <tr>
        <td><b>Last Name</b><br>{{ $lastName }}</td>
        <td><b>First Name</b><br>{{ $firstName }}</td>
        <td><b>M.I.</b><br>{{ $middleInitial }}</td>
        <td><b>Date of Birth</b><br>{{ $formatDate($dob) }}</td>
        <td><b>Social Security Number</b><br>{{ $ssn }}</td>
    </tr>
    <tr>
        <td colspan="3"><b>Home Address</b><br>{{ $address }}</td>
        <td><b>City</b><br>{{ $city }}</td>
        <td><b>State/Zip</b><br>{{ $employeeState }} {{ $zip }}</td>
    </tr>
    <tr>
        <td colspan="2"><b>Email</b><br>{{ $email }}</td>
        <td>
            <b>Gender</b><br>
            {!! $cb($gender === 'Male') !!} Male &nbsp;
            {!! $cb($gender === 'Female') !!} Female
            @if($gender === 'Other')
                &nbsp; {!! $cb(true) !!} Other
            @endif
        </td>
        <td><b>Salary</b><br>{{ $salary }}</td>
        <td><b>Hire Date</b><br>{{ $formatDate($hireDate) }}</td>
    </tr>
</table>

{{-- B. Health Care --}}
<table>
    <tr>
        <td colspan="5" class="section-title">B. Health Care Benefit Costs Per Bi-Weekly Pay</td>
    </tr>
    <tr>
        <td colspan="5">
            <b>1. UnitedHealthcare Medical:</b><br>
            <span class="small">Please check the box next to the plan you are electing.</span>
        </td>
    </tr>
    <tr>
        <th></th>
        <th>Option #1<br><span class="small">Open Access Plan</span></th>
        <th>Option #2<br><span class="small">HSA Plan ($5,000 Deductible)</span></th>
        <th><b>Waive Medical Coverage</b></th>
        <th><b>Reason for Waiving</b><br><span class="small">(Mandatory)</span></th>
    </tr>
    @foreach($medicalRows as $index => $row)
    <tr>
        <td><b>{{ $row['label'] }}</b></td>
        <td class="text-center amount-cell">
            {{ $healthOp1[$row['rate']] ?? '' }}
            {!! $cb($medicalPlan === $row['op1']) !!}
        </td>
        <td class="text-center amount-cell">
            {{ $healthOp2[$row['rate']] ?? '' }}
            {!! $cb($medicalPlan === $row['op2']) !!}
        </td>
        @if($index === 0)
        <td rowspan="4" class="text-center" style="vertical-align: middle;">
            {!! $cb($medicalPlan === 'waive') !!}
        </td>
        <td rowspan="4"><b>{{ $benefitEnrollment?->medical_waive_reason }}</b></td>
        @endif
    </tr>
    @endforeach
</table>

{{-- 2. Dental --}}
<table>
    <tr>
        <td colspan="6"><b>2. Principal Dental:</b> <span class="small">(Check one)</span></td>
    </tr>
    <tr>
        <th></th>
        <th class="text-center">Employee<br>{{ $dentalRates['employee'] ?? '' }}</th>
        <th class="text-center">Employee/Child(ren)<br>{{ $dentalRates['child'] ?? '' }}</th>
        <th class="text-center">Employee/Spouse<br>{{ $dentalRates['spouse'] ?? '' }}</th>
        <th class="text-center">Family<br>{{ $dentalRates['family'] ?? '' }}</th>
        <th class="text-center"><b>Waive Dental</b></th>
    </tr>
    <tr>
        <td><b>Dental Coverage</b></td>
        <td class="text-center">{!! $cb($dentalPlan === 'employee') !!}</td>
        <td class="text-center">{!! $cb($dentalPlan === 'child') !!}</td>
        <td class="text-center">{!! $cb($dentalPlan === 'spouse') !!}</td>
        <td class="text-center">{!! $cb($dentalPlan === 'family') !!}</td>
        <td class="text-center">{!! $cb($dentalPlan === 'waive') !!}</td>
    </tr>
</table>

{{-- 3. Vision --}}
<table>
    <tr>
        <td colspan="6"><b>3. Principal Vision:</b> <span class="small">(Check one)</span></td>
    </tr>
    <tr>
        <th></th>
        <th class="text-center">Employee<br>{{ $visionRates['employee'] ?? '' }}</th>
        <th class="text-center">Employee/Child(ren)<br>{{ $visionRates['child'] ?? '' }}</th>
        <th class="text-center">Employee/Spouse<br>{{ $visionRates['spouse'] ?? '' }}</th>
        <th class="text-center">Family<br>{{ $visionRates['family'] ?? '' }}</th>
        <th class="text-center"><b>Waive Vision</b></th>
    </tr>
    <tr>
        <td><b>Vision Coverage</b></td>
        <td class="text-center">{!! $cb($visionPlan === 'employee') !!}</td>
        <td class="text-center">{!! $cb($visionPlan === 'child') !!}</td>
        <td class="text-center">{!! $cb($visionPlan === 'spouse') !!}</td>
        <td class="text-center">{!! $cb($visionPlan === 'family') !!}</td>
        <td class="text-center">{!! $cb($visionPlan === 'waive') !!}</td>
    </tr>
</table>

{{-- C. Voluntary Life/AD&D --}}
<table>
    <tr>
        <td colspan="6" class="section-title">C. Voluntary Life/AD&amp;D Costs Per Bi-Weekly Pay</td>
    </tr>
    <tr>
        <td colspan="6">
            <b>Principal Voluntary Life/AD&amp;D</b> (Please write the amount of coverage you are electing as well as the cost per pay).<br>
            <span class="small">Please note: Employee must elect coverage for dependents to enroll. Spouse amount cannot exceed employee amount.</span>
        </td>
    </tr>
    <tr>
        <td colspan="3">{!! $cb($lifeOption === 'elect') !!} <b>Elect Coverage</b></td>
        <td colspan="3" style="color:#c00;">{!! $cb($lifeOption === 'waive') !!} <b>Waive Coverage</b></td>
    </tr>
    <tr>
        <td colspan="2"><b>Employee Life Amount:</b> {{ $formatMoney($benefitEnrollment?->emp_life_amount) }}</td>
        <td colspan="2"><b>Spousal Life Amount:</b> {{ $formatMoney($benefitEnrollment?->spouse_life_amount) }}</td>
        <td colspan="2"><b>Child Life Amount:</b> {{ $formatMoney($benefitEnrollment?->child_life_amount) }}</td>
    </tr>
    <tr>
        <td colspan="2"><b>Employee Cost:</b> {{ $formatMoney($benefitEnrollment?->emp_life_cost) }}</td>
        <td colspan="2"><b>Spousal Cost:</b> {{ $formatMoney($benefitEnrollment?->spouse_life_cost) }}</td>
        <td colspan="2"><b>Child Cost:</b> {{ $formatMoney($benefitEnrollment?->child_life_cost) }}</td>
    </tr>
</table>

{{-- D. Pre-Tax / After-Tax --}}
<table>
    <tr>
        <td colspan="2" class="section-title">
            D. I wish to defer the amounts selected above in Section B (Medical/Dental/Vision) from my pay: (Choose One)
        </td>
    </tr>
    <tr>
        <td width="35%">
            {!! $cb($taxMethod === 'pre_tax') !!} <b>Pre-Tax</b>
        </td>
        <td width="65%">
            <span class="small">By choosing pre-tax, taxable income is reduced and premiums are deducted before taxes are calculated.</span>
        </td>
    </tr>
    <tr>
        <td width="35%">
            {!! $cb($taxMethod === 'after_tax') !!} <b>After-Tax</b>
        </td>
        <td width="65%">
            <span class="small">By choosing after-tax, premiums are deducted from your pay after taxes are calculated.</span>
        </td>
    </tr>
</table>

<p class="auth-text">
    <b>YES,</b> I hereby make the above benefit elections for Plan Year {{ $benefitElection['plan_year'] ?? '' }}. I authorize my employer to deduct from my pay the
    amounts listed above to pay the premiums for myself and/or my dependents. I understand that coverage will not be effective until
    I complete all necessary enrollment forms for my above selected plans. I understand I cannot change or revoke any pre-tax
    election until the next open enrollment for the {{ $benefitElection['next_plan_year'] ?? '' }} plan year unless I have a qualifying change in family status such as:
    marriage, divorce, death of spouse or child, birth or adoption of a child, termination or commencement of employment of a spouse,
    change in my or my spouse's employment status from full-time to part-time or part-time to full-time, my spouse or I taking an
    unpaid leave of absence, and such other events as a plan administrator determines will permit a change or a revocation of an
    election. I understand that by participating in the Pre-Tax Plan, my Social Security benefits may be affected because the above
    elections will be deducted before my salary is taxed. Prior to each plan year, I will be offered the opportunity to change my benefit
    election for the following plan year.
</p>

<table class="noborder">
    <tr>
        <td width="70%">
            <div class="signature-line">{{ $employeeSign ?: trim($firstName . ' ' . $lastName) }}</div>
            <b>Signature</b>
        </td>
        <td width="30%" class="text-center">
            <div class="signature-line">{{ $formatDate($benefitEnrollment?->employee_date) }}</div>
            <b>Date</b>
        </td>
    </tr>
</table>

{{-- E. Dependents --}}
<table>
    <tr>
        <td colspan="7" class="section-title">E. Spouse / Eligible Dependent Child(ren) Information &mdash; Please complete all information for only those dependents enrolling in Medical, Dental and/or Vision insurance</td>
    </tr>
    <tr>
        <th>Last Name</th>
        <th>First Name</th>
        <th>Gender</th>
        <th>Date of Birth</th>
        <th>Social Security Number</th>
        <th>Relationship</th>
        <th>Coverage</th>
    </tr>
    @php
        $dependentRows = $dependents->values();
        while ($dependentRows->count() < 4) {
            $dependentRows->push(null);
        }
    @endphp
    @foreach($dependentRows->take(4) as $d)
    <tr>
        <td>{{ $d?->last_name }}</td>
        <td>{{ $d?->first_name }}</td>
        <td>
            @if($d)
                {!! $cb($d->gender === 'Male') !!} Male<br>
                {!! $cb($d->gender === 'Female') !!} Female
            @endif
        </td>
        <td>{{ $formatDate($d?->dob) }}</td>
        <td>{{ $d?->ssn }}</td>
        <td>{{ $d?->relationship }}</td>
        <td>
            @if($d)
                {!! $cb(!empty($d->medical)) !!} Medical<br>
                {!! $cb(!empty($d->dental)) !!} Dental<br>
                {!! $cb(!empty($d->vision)) !!} Vision
            @endif
        </td>
    </tr>
    @endforeach
</table>

</body>
</html>
