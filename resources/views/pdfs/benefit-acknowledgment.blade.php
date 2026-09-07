<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Benefits Acknowledgement</title>
    <style>
        @page {
            size: letter;
            margin: 0.5in 0.5in 0.5in 0.5in;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: "Times New Roman", serif;
            font-size: 11pt;   
            color: #000;
        }
        .title {
            text-align: center;
            font-family: "Times New Roman", serif;
            font-size: 16pt;
            font-weight: bold;
            margin: 0 0 18pt;
        }
        p {
            margin: 0 0 11pt;
            text-align: left;
        }
        .section-heading {
            font-weight: bold;
            font-style: italic;
            margin-top: 12pt;
        }
        .ack-checkbox {
            display: inline-block;
            width: 11px;
            height: 11px;
            border: 1px solid #000;
            background: #fff;
            vertical-align: middle;
            margin-right: 6px;
            text-align: center;
            line-height: 11px;
            font-size: 10px;
            font-family: "DejaVu Sans", sans-serif;
        }
        .bullet {
            margin: 0 0 6pt;
            padding-left: 18pt;
            text-indent: -18pt;
        }
        .bullet-char {
            font-family: "DejaVu Sans", sans-serif;
        }
        .signing-note {
            font-style: italic;
            margin-top: 12pt;
            margin-bottom: 18pt;
        }
        .sig-line {
            margin: 0 0 18pt;
            line-height: 1.6;
        }
        .sig-label {
            display: inline;
        }
        .sig-value {
            display: inline-block;
            min-width: 260pt;
            border-bottom: 1px solid #000;
            line-height: 1.2;
            padding-bottom: 1pt;
        }
        .sig-value.name {
            min-width: 320pt;
        }
        .sig-value.signature {
            min-width: 340pt;
        }
        .footnote {
            margin-top: 6pt;
        }
    </style>
</head>
<body>
@php
    $benefitEnrollment = $benefitEnrollment ?? null;
    $formatDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('m-d-Y') : '';

    $ackDate = $formatDate($benefitEnrollment?->waive_ack_date);
    $ackName = $benefitEnrollment?->waive_ack_name ?? '';
    $ackSignature = $benefitEnrollment?->waive_ack_signature ?? '';
    $isDecline = ($benefitEnrollment?->benefit_acknowledgment ?? '') === 'decline';
@endphp

<div class="title">Benefits Acknowledgement</div>

<p>
    I acknowledge that I have been offered the option to enroll myself and eligible family members in the Company's
    Group Health Plan, and that coverage is entirely voluntary. I also understand that my employer has offered me a
    compliant health plan as defined by the Affordable Care Act (ACA). I am aware that in order to qualify for benefits, I
    must meet the eligibility requirement of working an average of at least 30 hours per week.
</p>

<p>
    I understand that benefit eligibility begins after 60 days of employment, and that should I meet the qualification
    criteria, the Human Resources department will make three attempts to contact me via email, providing me with a
    Benefits Election Form and information regarding the medical, dental, and vision plans that are offered. In this case,
    I will be given 5 calendar days to select my benefits plan.
</p>

<p class="section-heading">
    <span class="ack-checkbox">{{ $isDecline ? '' : '✓' }}</span>
    If I qualify and opt in for benefits, I understand that:
</p>
<p class="bullet"><span class="bullet-char">➢</span> The Company will only contribute to individual plans, and that the same healthcare plans are available for dependents, at a voluntary contribution.</p>
<p class="bullet"><span class="bullet-char">➢</span> I am authorizing the Company to deduct the agreed-upon premium amount from my biweekly paychecks.</p>
<p class="bullet"><span class="bullet-char">➢</span> Coverage will not be effective until I complete all necessary enrollment forms for my selected plans.</p>

<p class="section-heading">
    <span class="ack-checkbox">{{ $isDecline ? '✓' : '' }}</span>
    If I decline benefits coverage through the Parent Company, I understand that:
</p>
<p class="bullet"><span class="bullet-char">➢</span> I may not be offered another opportunity to participate until during the next open enrollment period.</p>
<p class="bullet"><span class="bullet-char">➢</span> By electing not to enroll in this ACA compliant plan, I will not be eligible for a premium subsidy at either a state-based or federally-operated insurance exchange.</p>

<p class="section-heading">If I qualify for benefits and do not submit my Benefits Enrollment Form by the deadline, I understand that:</p>
<p class="bullet"><span class="bullet-char">➢</span> The Company will accept my attestation below, as authorization to waive my participation in the Company's group health plan.</p>
<p class="bullet"><span class="bullet-char">➢</span> This does not disqualify me from enrolling in benefits in the next open enrollment period, provided I continue to meet the previously outlined eligibility requirements.</p>

<p class="signing-note">
    By signing my name electronically on this form, I am agreeing that my electronic signature is the legal equivalent of my manual signature.
</p>

<p class="sig-line">
    <span class="sig-label">Date:</span>
    <span class="sig-value">{{ $ackDate }}</span>
</p>
<p class="sig-line">
    <span class="sig-label">Name:</span>
    <span class="sig-value name">{{ $ackName }}</span>
</p>
<p class="sig-line">
    <span class="sig-label">Signature:</span>
    <span class="sig-value signature">{{ $ackSignature }}</span>
</p>

<p class="footnote">
    **Please note, that if you have any questions about your coverage options, need more time to finalize your decision,
    or have missed the deadline to submit your election form, you can reach out to the Human Resources department
    In order to request a deadline extension, please contact HR at least 24 hours before the deadline to allow our HR
    manager enough time to process your request. Extension requests received after this period are not guaranteed
    and may not be granted.
</p>

</body>
</html>
