<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; }
        .container { max-width: 960px; margin: 0 auto; padding: 20px; }
        .table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; margin-bottom: 20px; }
        h2 { color: #1a1a1a; border-bottom: 2px solid #d97706; padding-bottom: 8px; margin-top: 0; }
        .meta { background: #f1f5f9; padding: 12px; border-radius: 6px; margin-bottom: 20px; }
        table { width: 100%; min-width: 640px; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border: 1px solid #e2e8f0; }
        th { background: #fef3c7; font-weight: 600; }
        .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Overtime from employee hours upload</h2>
        <p>The following lines were imported with overtime hours &gt; 0.</p>

        <div class="meta">
            <strong>Uploaded by:</strong> {{ $uploadedBy ?? 'System' }}<br>
            <strong>Lines:</strong> {{ count($rows ?? []) }}
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Pay date</th>
                        <th>Total hours</th>
                        <th>Overtime hours</th>
                        <th>Role</th>
                        <th>Company</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows ?? [] as $r)
                    <tr>
                        <td>{{ $r['pos_employee_id'] !== '' ? $r['pos_employee_id'] : '—' }}</td>
                        <td>{{ $r['employee_name'] !== '' ? $r['employee_name'] : '—' }}</td>
                        <td>{{ $r['date'] }}</td>
                        <td>{{ number_format($r['total_hours'], 2) }}</td>
                        <td>{{ number_format($r['overtime_hours'], 2) }}</td>
                        <td>{{ $r['role'] }}</td>
                        <td>{{ $r['company'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="footer">
            This message was sent because your role includes the overtime notification (or HR fallback when none are configured).
        </div>
    </div>
</body>
</html>
