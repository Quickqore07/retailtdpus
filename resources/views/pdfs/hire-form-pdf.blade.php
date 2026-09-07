<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Employment Eligibility Verification</title>
    <style type="text/css">
        @page { margin: 10px 20px; }
        .hire_wrapper {
            max-width: 1000px;
            margin: auto;
            padding: 10px;
            box-shadow: 0 14px 28px rgba(0, 0, 0, 0.25), 0 10px 10px rgba(0, 0, 0, 0.22);
        }
        body {
            font-family: "Georama", "DejaVu Sans", sans-serif;
            font-size: 14px;
            line-height: 18px;
        }
    </style>
</head>
<body>

<div class="hire_wrapper">
    <div class="inner">
        <div style="text-align:center;">
            <h2>Hire Form</h2>
        </div>

@php
    $onboarding = $onboarding ?? null;
    $employee = $onboarding?->employee ?? null;
    $company = $onboarding?->company ?? $employee?->company ?? null;
    $emergencyContact = $emergencyContact ?? null;

    $storeName = $company ? ($company->store_number ? $company->store_number . ' - ' . $company->name : $company->name) : '';
    $storeAddress = $company?->address ?? '';

    $firstName = ucwords(strtolower(trim($employee?->first_name ?? $onboarding?->applicant_first_name ?? '')));
    $lastName = ucwords(strtolower(trim($employee?->last_name ?? $onboarding?->applicant_last_name ?? '')));
    $phone = $employee?->phone ?? $onboarding?->applicant_contact_number ?? '';
    $email = $employee?->email ?? $onboarding?->applicant_email ?? '';
    $street = $onboarding?->applicant_address ?? $employee?->street ?? '';
    $city = $onboarding?->city ?? $employee?->city ?? '';
    $state = $onboarding?->state ?? $employee?->state ?? '';
    $zip = $onboarding?->zip_code ?? $employee?->zip ?? '';

    $contactName = $emergencyContact
        ? trim(($emergencyContact->e_emergency_contact_first_name ?? '') . ' ' . ($emergencyContact->e_emergency_contact_last_name ?? ''))
        : ($employee?->emergency_contact_name ?? '');
    $contactPhone1 = $emergencyContact?->e_emergency_contact_phone_cell ?? $employee?->emergency_contact_phone ?? '';
    $contactPhone2 = $emergencyContact?->e_emergency_contact2_phone_cell ?? '';
    $contactRelationship = $emergencyContact?->e_emergency_contact_relationship ?? $employee?->emergency_contact_relationship ?? '';

    $hiringDate = $onboarding?->doj ?? $employee?->hire_date ?? null;
    $formatDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('m-d-Y') : '';

    $applicantHireDetail = isset($onboarding?->applicant_hire_detail) ? $onboarding?->applicant_hire_detail : [];
    if (!is_array($applicantHireDetail)) {
        $applicantHireDetail = [];
    }

    $employeeRatesByRole = [];
    if ($employee && $company) {
        $rates = \App\Models\Payroll\EmployeeRates::where('employee_id', $employee->id)
            ->where('company_id', $company->id)
            ->with('role')
            ->orderBy('effective_date', 'desc')
            ->get();
        foreach ($rates as $r) {
            $rid = $r->role_id;
            if (!isset($employeeRatesByRole[$rid])) {
                $employeeRatesByRole[$rid] = $r;
            }
        }
    }

    if (empty($applicantHireDetail) && $employee && $company) {
        $rates = \App\Models\Payroll\EmployeeRates::where('employee_id', $employee->id)
            ->where('company_id', $company->id)
            ->whereIn('rate_type', ['Payroll Regular', 'Payroll Slab', 'Payroll 1099', '1099 Regular', '1099 Slab'])
            ->with('role')
            ->orderBy('effective_date', 'desc')
            ->get();
        foreach ($rates as $r) {
            $applicantHireDetail[] = (object)[
                'position' => $r->role_id,
                'role' => $r->role,
                'rate_type' => $r->rate_type,
                'payroll_rate' => $r->payroll_rate ?? 0,
                'ten99_rate' => $r->ten99_rate ?? 0,
                'slab_first_hours' => $r->slab_first_hours ?? 0,
                'slab_rest_rate' => $r->slab_rest_rate ?? 0,
                'rate' => $r->rate ?? 0,
            ];
        }
    }
@endphp

        <table border="1" cellspacing="0" cellpadding="8" style="width: 100%; border-collapse: collapse; font-family: Arial, sans-serif;">
            <thead>
                <tr>
                    <th colspan="6" style="background-color: #a9c5e9; text-align: center; font-size: 16px;">
                        <b style="display:inline-block;width: 100%;">{{ $storeName }}</b>
                        <b> {{ $storeAddress }}</b>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>First Name</td>
                    <td>{{ $firstName }}</td>
                    <td>Last Name</td>
                    <td>{{ $lastName }}</td>
                    <td>Phone #</td>
                    <td>{{ $phone }}</td>
                </tr>
                <tr>
                    <td>Email ID</td>
                    <td>{{ $email }}</td>
                    <td>Street</td>
                    <td>{{ $street }}</td>
                    <td>City</td>
                    <td>{{ $city }}</td>
                </tr>
                <tr>
                    <td>State</td>
                    <td>{{ $state }}</td>
                    <td>ZIP</td>
                    <td>{{ $zip }}</td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td>Contact Person</td>
                    <td>{{ $contactName }}</td>
                    <td>Phone #</td>
                    <td>{{ $contactPhone1 }}</td>
                    <td>Phone # / Relationship</td>
                    <td>{{ $contactPhone2 ?: $contactRelationship }}</td>
                </tr>

                @foreach ($applicantHireDetail as $list)
                    @php
                        $item = is_array($list) ? (object) $list : $list;
                        $positionId = $item->position ?? $item->role_id ?? null;
                        $positionDisplay = null;
                        $rateType = null;
                        $displayRate = null;

                        if (isset($item->role) && $item->role) {
                            $positionDisplay = $item->role->name ?? 'Unknown';
                        } elseif (is_numeric($positionId)) {
                            $role = \App\Models\Settings\EmployeeRoles::authorizedWorkgroup()->find($positionId);
                            $positionDisplay = $role ? $role->name : 'Unknown';
                        } else {
                            $positionDisplay = $positionId ?? 'Unknown';
                        }

                        if (isset($item->rate_type)) {
                            $rateType = $item->rate_type;
                            if (in_array($rateType, ['Payroll Regular', 'Payroll 1099'])) {
                                $displayRate = number_format((float)($item->payroll_rate != 0 ? $item->payroll_rate : ($item->rate ?? 0)), 2);
                            } elseif ($rateType === 'Payroll Slab') {
                                $first = (float)($item->slab_first_hours ?? 0);
                                $rest = (float)($item->slab_rest_rate ?? 0);
                                $displayRate = $first > 0 ? $first . ' hrs @ ' . number_format($rest, 2) : number_format($rest, 2);
                            } elseif (in_array($rateType, ['1099 Regular', '1099 Slab'])) {
                                $displayRate = number_format((float)($item->ten99_rate ?? $item->rate ?? 0), 2);
                            } else {
                                $displayRate = number_format((float)($item->payroll_rate ?? $item->ten99_rate ?? $item->rate ?? $item->salaryAmount ?? $item->fixedAmount ?? $item->slabPayRate2 ?? 0), 2);
                            }
                        } else {
                            $empRate = $positionId && isset($employeeRatesByRole[$positionId])
                                ? $employeeRatesByRole[$positionId]
                                : (count($employeeRatesByRole) ? reset($employeeRatesByRole) : null);
                            if ($empRate) {
                                $rateType = $empRate->rate_type;
                                if (in_array($rateType, ['Payroll Regular', 'Payroll 1099'])) {
                                    $displayRate = number_format((float)($empRate->payroll_rate ?? $empRate->rate ?? 0), 2);
                                } elseif ($rateType === 'Payroll Slab') {
                                    $first = (float)($empRate->slab_first_hours ?? 0);
                                    $rest = (float)($empRate->slab_rest_rate ?? 0);
                                    $displayRate = $first > 0 ? $first . ' hrs @ ' . number_format($rest, 2) : number_format($rest, 2);
                                } elseif (in_array($rateType, ['1099 Regular', '1099 Slab'])) {
                                    $displayRate = number_format((float)($empRate->ten99_rate ?? $empRate->rate ?? 0), 2);
                                } else {
                                    $displayRate = number_format((float)($empRate->rate ?? 0), 2);
                                }
                            } else {
                                $rolPayCheck = (float)($item->salaryAmount ?? $item->fixedAmount ?? $item->slabPayRate2 ?? 0);
                                $displayRate = number_format($rolPayCheck, 2);
                                $rateType = $item->rate_type ?? '-';
                            }
                        }
                    @endphp
                    <tr>
                        <td>Position-{{ $loop->index + 1 }}</td>
                        <td>{{ $positionDisplay }}</td>
                        <td>Rate ({{ $rateType ?? '-' }})</td>
                        <td>{{ $displayRate }}</td>
                        <td>Date of Hiring</td>
                        <td>{{ $formatDate($hiringDate) }}</td>
                    </tr>
                @endforeach
                @if (empty($applicantHireDetail))
                    <tr>
                        <td>Date of Hiring</td>
                        <td colspan="5">{{ $formatDate($hiringDate) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
