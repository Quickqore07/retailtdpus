<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Onboarding Resubmitted</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f8fafc; margin: 0; padding: 20px;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px;">
        <h2 style="margin-top: 0; color: #111827;">Onboarding resubmitted</h2>
        <p style="color: #374151;">The onboarding form has been submitted again.</p>
        <p style="color: #111827; margin: 0 0 8px 0;">
            <strong>Employee:</strong> {{ $employeeFullName }}
        </p>
        @if(!empty($onboardingNumber))
            <p style="color: #111827; margin: 0;">
                <strong>Onboarding #:</strong> {{ $onboardingNumber }}
            </p>
        @endif
    </div>
</body>
</html>
