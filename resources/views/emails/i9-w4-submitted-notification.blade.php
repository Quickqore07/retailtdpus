<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $formLabel }} submitted</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; padding: 20px; background: #f1f1f1; color: #333; }
        .container { background: #fff; max-width: 640px; margin: 0 auto; padding: 24px; border-radius: 8px; }
        h1 { font-size: 20px; margin: 0 0 16px; color: #b1151d; }
        table.meta { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        table.meta th { text-align: left; width: 36%; padding: 8px 8px 8px 0; color: #b1151d; vertical-align: top; }
        table.meta td { padding: 8px 0; }
        a.button { display: inline-block; margin-top: 8px; padding: 10px 18px; background: #b1151d; color: #fff !important; text-decoration: none; border-radius: 6px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>{{ $formLabel }} {{ $isResubmit ? 'resubmitted' : 'submitted' }}</h1>
        <p>
            An employee {{ $isResubmit ? 'resubmitted' : 'submitted' }} their <strong>{{ $formLabel }}</strong>
            via {{ $sourceLabel }}.
        </p>
        <table class="meta">
            <tr><th>Employee</th><td>{{ $employeeName }}</td></tr>
            @if($employeeId !== '')
                <tr><th>Employee ID</th><td>{{ $employeeId }}</td></tr>
            @endif
            @if($onboardingNumber !== '')
                <tr><th>Onboarding #</th><td>{{ $onboardingNumber }}</td></tr>
            @endif
        </table>
        <p>
            <a class="button" href="{{ $reviewUrl }}">Open I-9 review</a>
        </p>
        
    </div>
</body>
</html>
