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
        .employee-info { background: #f8fafc; padding: 12px; border-radius: 6px; margin-bottom: 20px; }
        table { width: 100%; min-width: 700px; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border: 1px solid #e2e8f0; }
        th { background: #f1f5f9; font-weight: 600; }
        .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; }
        @media only screen and (max-width: 600px) {
            .container { padding: 12px 15px; }
            h2 { font-size: 18px; }
            .employee-info { padding: 10px; font-size: 14px; }
            .table-wrapper { margin-left: -15px; margin-right: -15px; padding: 0 15px; }
            th, td { padding: 8px 6px; font-size: 13px; white-space: nowrap; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Employee Hours Request</h2>
        <p>The following employee hours entry has been submitted and is awaiting your approval.</p>

        <div class="employee-info">
            <strong>Employee:</strong> {{ $employee->pos_name ?? $employee->employee_id ?? 'N/A' }}<br>
            <strong>Requested by:</strong> {{ $requestedBy ?? 'System' }}
        </div>

        <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Company</th>
                    <th>Role</th>
                    <th>Hours</th>
                    <th>Rate</th>
                    <th>Tips</th>
                    <th>Tips Due</th>
                    <th>Mileage</th>
                    <th>Mileage Due</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requests as $req)
                <tr>
                    <td>{{ $req['date'] ?? '-' }}</td>
                    <td>{{ $req['company'] ?? '-' }}</td>
                    <td>{{ $req['role'] ?? '-' }}</td>
                    <td>{{ $req['total_hours'] ?? '-' }}</td>
                    <td>{{ $req['pay_rate'] ?? '-' }}</td>
                    <td>{{ $req['tips'] ?? '-' }}</td>
                    <td>{{ $req['tips_due'] ?? '-' }}</td>
                    <td>{{ $req['mileage_excess'] ?? '-' }}</td>
                    <td>{{ $req['mileage_due'] ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>

        <div class="footer">
            Please log in to the system to review and approve this request.
        </div>
    </div>
</body>
</html>
