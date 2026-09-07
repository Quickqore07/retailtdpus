<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Onboarding Note</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f8fafc; margin: 0; padding: 20px;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 24px;">
        <h2 style="margin-top: 0; color: #111827;">Onboarding update</h2>
        <p style="color: #374151;">Hi {{ $employeeName }},</p>
        <p style="color: #374151;">
            Your onboarding form needs updates before it can be approved.
            @if(!empty($onboardingNumber))
                (Onboarding #{{ $onboardingNumber }})
            @endif
        </p>
        <p style="color: #111827; font-weight: 600; margin-bottom: 8px;">HR Note:</p>
        <div style="background: #f3f4f6; border-left: 4px solid #ef4444; padding: 12px; color: #1f2937; white-space: pre-wrap;">{{ $note }}</div>
        <p style="margin-top: 20px; color: #374151;">Please review the note, update your onboarding form, and submit again.</p>
        <p style="color: #374151; margin-bottom: 0;">Thank you.</p>
    </div>
</body>
</html>
