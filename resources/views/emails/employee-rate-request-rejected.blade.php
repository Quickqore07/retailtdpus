<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; }
        .container { max-width: 640px; margin: 0 auto; padding: 20px; }
        h2 { color: #1a1a1a; border-bottom: 2px solid #dc2626; padding-bottom: 8px; margin-top: 0; }
        .box { background: #fef2f2; padding: 14px; border-radius: 6px; margin: 16px 0; border: 1px solid #fecaca; }
        .reason { background: #fff; padding: 12px; border-radius: 4px; border: 1px solid #e5e7eb; white-space: pre-wrap; }
        .footer { margin-top: 24px; font-size: 12px; color: #64748b; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Employee rate request rejected</h2>
        <p>The employee rate request listed below has been rejected.</p>

        <div class="box">
            <strong>Employee:</strong> {{ $employeeName }}<br>
            @if(!empty($companyName))
                <strong>Company:</strong> {{ $companyName }}<br>
            @endif
            @if(!empty($roleLabel))
                <strong>Role:</strong> {{ $roleLabel }}<br>
            @endif
            @if(!empty($effectiveDate))
                <strong>Effective date:</strong> {{ $effectiveDate }}<br>
            @endif
            <strong>Rejected by:</strong> {{ $rejectedByName ?? 'System' }}
        </div>

        <p><strong>Reason</strong></p>
        <div class="reason">{{ $reason }}</div>

        <div class="footer">
            This message was sent automatically. Please contact HR if you have questions.
        </div>
    </div>
</body>
</html>
