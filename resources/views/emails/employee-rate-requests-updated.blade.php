<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; }
        .container { max-width: 700px; margin: 0 auto; padding: 20px; }
        h2 { color: #1a1a1a; border-bottom: 2px solid #f59e0b; padding-bottom: 8px; margin-top: 0; }
        .employee-info { background: #f8fafc; padding: 12px; border-radius: 6px; margin-bottom: 20px; }
        .request-block { margin-bottom: 24px; padding: 16px; border: 1px solid #e2e8f0; border-radius: 8px; }
        .request-block h3 { margin: 0 0 12px 0; font-size: 14px; color: #475569; }
        .compare-row { padding: 6px 0; border-bottom: 1px solid #f1f5f9; }
        .compare-row:last-child { border-bottom: none; }
        .old-value { color: #94a3b8; text-decoration: line-through; font-size: 13px; }
        .new-value { color: #059669; font-weight: 600; }
        .unchanged { color: #64748b; }
        .footer { margin-top: 30px; padding-top: 15px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; }
        @media only screen and (max-width: 600px) {
            .container { padding: 12px 15px; }
            h2 { font-size: 18px; }
            .employee-info { padding: 10px; font-size: 14px; }
            .request-block { padding: 12px; margin-bottom: 20px; }
            .request-block h3 { font-size: 13px; word-break: break-word; }
            .compare-row { font-size: 14px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Updated Employee Rate Requests</h2>
        <p>The following rate requests have been updated and are awaiting your approval.</p>

        <div class="employee-info">
            <strong>Employee:</strong> {{ $employee->pos_name ?? $employee->employee_id ?? 'N/A' }}<br>
            <strong>Updated by:</strong> {{ $updatedBy ?? 'System' }}
        </div>

        @foreach($requests as $req)
        <div class="request-block">
            <h3>Request: {{ $req['role'] ?? '-' }} | {{ $req['company'] ?? '-' }} | Effective: {{ $req['effective_date'] ?? '-' }}</h3>
            @foreach($req['changes'] ?? [] as $field => $change)
            <div class="compare-row">
                <strong>{{ $change['label'] ?? $field }}:</strong>
                @if(isset($change['old']) && isset($change['new']) && $change['old'] != $change['new'])
                    <span class="old-value">{{ $change['old'] }}</span>
                    <span> → </span>
                    <span class="new-value">{{ $change['new'] }}</span>
                @else
                    <span class="unchanged">{{ $change['new'] ?? $change['old'] ?? '-' }}</span>
                @endif
            </div>
            @endforeach
        </div>
        @endforeach

        <div class="footer">
            Please log in to the system to review and approve these updated requests.
        </div>
    </div>
</body>
</html>
