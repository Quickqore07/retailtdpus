@php
    $headlines = [
        'created' => 'Purchase invoice created',
        'approved' => 'Purchase invoice approved',
    ];
    $headline = $headlines[$action] ?? 'Purchase invoice notification';
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
            @if($action === 'approved')
                A purchase invoice was approved by <strong>{{ $actorName }}</strong>.
            @else
                A purchase invoice was created by <strong>{{ $actorName }}</strong>.
            @endif
        </p>
        <table class="meta">
            <tr><th>Store</th><td>{{ $invoice->company?->name ?? '—' }}</td></tr>
            <tr><th>Invoice number</th><td>{{ $invoice->invoice_no ?: '—' }}</td></tr>
            <tr><th>Invoice date</th><td>{{ $fmtDate($invoice->invoice_date) }}</td></tr>
            <tr><th>Due date</th><td>{{ $fmtDate($invoice->due_date) }}</td></tr>
            <tr><th>Vendor</th><td>{{ $invoice->vendor?->name ?? '—' }}</td></tr>
            <tr><th>Expense type</th><td>{{ $invoice->expense?->name ?? '—' }}</td></tr>
            <tr><th>Amount</th><td>{{ $fmtMoney($invoice->amount) }}</td></tr>
            <tr><th>Other amount</th><td>{{ $fmtMoney($invoice->other_amount) }}</td></tr>
            <tr><th>Total amount</th><td>{{ $fmtMoney($invoice->total_amount) }}</td></tr>
            <tr><th>Status</th><td>{{ ucfirst((string) ($invoice->status ?? '—')) }}</td></tr>
        </table>
        <p>
            <a class="button" href="{{ $reviewUrl }}">View purchase invoice</a>
        </p>
        
    </div>
</body>
</html>
