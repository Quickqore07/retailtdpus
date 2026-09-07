<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; }
        .container { max-width: 900px; margin: 0 auto; padding: 20px; }
        .table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; margin-bottom: 20px; }
        h2 { color: #1a1a1a; border-bottom: 2px solid #3b82f6; padding-bottom: 8px; margin-top: 0; }
        h3 { color: #1e293b; font-size: 16px; margin: 24px 0 12px; padding-bottom: 6px; border-bottom: 1px solid #e2e8f0; }
        .employee-info { background: #f8fafc; padding: 12px; border-radius: 6px; margin-bottom: 12px; }
        .meta { background: #f1f5f9; padding: 12px; border-radius: 6px; margin-bottom: 20px; }
        table { width: 100%; min-width: 700px; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border: 1px solid #e2e8f0; }
        th { background: #f1f5f9; font-weight: 600; }
        .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; }
        @media only screen and (max-width: 600px) {
            .container { padding: 12px 15px; }
            h2 { font-size: 18px; }
            h3 { font-size: 15px; }
            .employee-info { padding: 10px; font-size: 14px; }
            .table-wrapper { margin-left: -15px; margin-right: -15px; padding: 0 15px; }
            th, td { padding: 8px 6px; font-size: 13px; white-space: nowrap; }
        }
    </style>
</head>
<body>
    @php
        $groups = $employeeGroups ?? (isset($employee, $requests) ? [['employee' => $employee, 'requests' => $requests]] : []);
        $employeeCount = count($groups);
    @endphp
    <div class="container">
        <h2>New Employee Rate Requests</h2>
        <p>The following new rate requests have been submitted and are awaiting your approval.</p>

        <div class="meta">
            <strong>Requested by:</strong> {{ $requestedBy ?? 'System' }}<br>
            @if($employeeCount > 1)
                <strong>Employees:</strong> {{ $employeeCount }}
            @endif
        </div>

        @foreach($groups as $group)
            @php
                $emp = $group['employee'];
                $reqs = $group['requests'];
            @endphp
            @if($employeeCount > 1)
                <h3>{{ $emp->pos_name ?? $emp->employee_id ?? 'N/A' }}</h3>
            @else
                <div class="employee-info">
                    <strong>Employee:</strong> {{ $emp->pos_name ?? $emp->employee_id ?? 'N/A' }}
                </div>
            @endif

            <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Company</th>
                        <th>Pay Type</th>
                        <th>Rate Type</th>
                        <th>Payroll Type</th>
                        <th>Rate</th>
                        <th>Slab First Hours</th>
                        <th>Slab Rest Rate</th>
                        <th>Payroll Hours</th>
                        <th>Check Payment Amount</th>
                        <th>Effective Date</th>
                        <th>Till Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reqs as $req)
                    <tr>
                        <td>{{ $req['role'] ?? '-' }}</td>
                        <td>{{ $req['company'] ?? '-' }}</td>
                        <td>{{ $req['pay_type'] ?? '-' }}</td>
                        <td>{{ $req['rate_type'] ?? '-' }}</td>
                        <td>{{ $req['payroll_type'] ?? '-' }}</td>
                        <td>{{ $req['rate_formatted'] ?? $req['rate'] ?? '-' }}</td>
                        <td>{{ $req['slab_first_hours'] ?? '-' }}</td>
                        <td>{{ $req['slab_rest_rate'] ?? '-' }}</td>
                        <td>{{ $req['payroll_hours'] ?? '-' }}</td>
                        <td>{{ $req['check_payment_amount'] ?? '-' }}</td>
                        <td>{{ $req['effective_date'] ?? '-' }}</td>
                        <td>{{ $req['till_date'] ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        @endforeach

        <div class="footer">
            Please log in to the system to review and approve these requests.
        </div>
    </div>
</body>
</html>
