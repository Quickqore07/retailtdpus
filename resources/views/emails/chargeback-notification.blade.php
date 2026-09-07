@php
    $headlines = [
        'submitted' => 'Chargeback submitted to processor',
        'updated' => 'Chargeback updated',
        'sales_receipt_uploaded' => 'Sales receipt uploaded',
        'sales_receipt_reuploaded' => 'Sales receipt reuploaded',
    ];
    $headline = $headlines[$action] ?? 'Chargeback notification';
    $fmtMoney = static function ($v) {
        if ($v === null || $v === '') {
            return '—';
        }

        return '$'.number_format((float) $v, 2);
    };
    $fmtDate = static function ($v) {
        if ($v === null || $v === '') {
            return '—';
        }

        try {
            return $v instanceof \DateTimeInterface
                ? $v->format('M j, Y')
                : \Illuminate\Support\Carbon::parse($v)->toFormattedDateString();
        } catch (\Throwable $e) {
            return (string) $v;
        }
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ $headline }}</title>
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
        <h1>{{ $headline }}</h1>
        <p>
            @if($action === 'submitted')
                A chargeback was submitted to the processor by <strong>{{ $actorName }}</strong>.
            @elseif($action === 'updated')
                A chargeback was updated by <strong>{{ $actorName }}</strong>.
            @elseif($action === 'sales_receipt_reuploaded')
                A sales receipt was reuploaded for a chargeback by <strong>{{ $actorName }}</strong>.
            @else
                A sales receipt was uploaded for a chargeback by <strong>{{ $actorName }}</strong>.
            @endif
        </p>
        <table class="meta">
            <tr><th>Store</th><td>{{ $chargeback->company?->name ?? '—' }}</td></tr>
            <tr><th>Case number</th><td>{{ $chargeback->case_number ?: '—' }}</td></tr>
            <tr><th>Reference number</th><td>{{ $chargeback->reference_number ?: '—' }}</td></tr>
            <tr><th>Amount</th><td>{{ $fmtMoney($chargeback->amount) }}</td></tr>
            <tr><th>Reason code</th><td>{{ $chargeback->reason_code ? $chargeback->reason_code.' — '.$reasonLabel : '—' }}</td></tr>
            <tr><th>Card network</th><td>{{ $chargeback->card_network ?: '—' }}</td></tr>
            <tr><th>Card last four</th><td>{{ $chargeback->card_last_four ?: '—' }}</td></tr>
            <tr><th>Processor due date</th><td>{{ $fmtDate($chargeback->processor_due_date) }}</td></tr>
            <tr><th>Status</th><td>{{ data_get($chargeback, 'display_status.label', '—') }}</td></tr>
        </table>
        <p>
            <a class="button" href="{{ $reviewUrl }}">Open chargebacks</a>
        </p>
        
    </div>
</body>
</html>
