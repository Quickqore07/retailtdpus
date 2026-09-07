@php
    use Illuminate\Support\Carbon;
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
            return Carbon::parse($v)->toFormattedDateString();
        } catch (\Throwable $e) {
            return (string) $v;
        }
    };
    $roleLabel = static function ($rate) {
        $r = $rate->role;
        if ($r && $r->name) {
            return $r->code.' - '.$r->name;
        }
        return $rate->role_id ? (string) $rate->role_id : '—';
    };
    $showsSlabFirst = static function ($rateType) {
        return in_array($rateType, ['Payroll Slab', 'Payroll 1099', '1099 1099', '1099 Slab'], true);
    };
    $showsPayrollType = static function ($rateType) {
        return in_array($rateType, ['Payroll Regular', 'Payroll Slab', 'Payroll 1099'], true);
    };
    $showsCheckPayment = static function ($rateType) {
        return in_array($rateType, ['1099 Regular', '1099 Slab', 'Payroll Slab', 'Payroll 1099'], true);
    };
    $checkPaymentLabel = static function ($rate) use ($fmtMoney) {
        if (($rate->check_payment_type ?? '') === 'percentage') {
            return ($rate->check_payment_amount ?? '0').'%';
        }
        return $fmtMoney($rate->check_payment_amount ?? null);
    };
    $payrollHoursLabel = static function ($rate) {
        if (! $rate->payroll_hours) {
            return '—';
        }
        $u = ($rate->payroll_hours_type ?? '') === 'percentage' ? '%' : ' Hours';
        return $rate->payroll_hours.$u;
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Employee hired</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; padding: 20px; background: #f1f1f1; color: #333; }
        .container { background: #fff; max-width: 720px; margin: 0 auto; padding: 24px; border-radius: 8px; }
        h1 { font-size: 20px; margin: 0 0 8px; color: #b1151d; }
        .sub { margin: 0 0 20px; color: #666; font-size: 14px; }
        table.meta { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        table.meta th { text-align: left; width: 34%; padding: 6px 8px 6px 0; color: #b1151d; vertical-align: top; font-size: 14px; }
        table.meta td { padding: 6px 0; font-size: 14px; }
        .rate-card { border: 1px solid #ddd; border-radius: 8px; padding: 16px; margin-bottom: 16px; background: #fafafa; }
        .rate-card h2 { font-size: 15px; margin: 0 0 12px; color: #444; }
        table.grid { width: 100%; border-collapse: collapse; font-size: 13px; }
        table.grid th, table.grid td { border: 1px solid #e5e5e5; padding: 8px; text-align: left; vertical-align: top; }
        table.grid th { background: #f0f0f0; width: 32%; color: #555; }
        .note { margin-top: 10px; font-size: 13px; color: #555; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Employee hired</h1>
        <p class="sub">A rate request was approved and the employee was marked <strong>Completed</strong>. Approved by {{ $approvedByName }}.</p>

        <table class="meta">
            <tr><th>POS name</th><td>{{ $employee->pos_name ?? '—' }}</td></tr>
            <tr><th>Employee ID</th><td>{{ $employee->employee_id ?? '—' }}</td></tr>
            <tr><th>Hire date</th><td>{{ $fmtDate($employee->hire_date ?? null) }}</td></tr>
            <tr><th>Email</th><td>{{ $employee->email ?? '—' }}</td></tr>
        </table>

        <p style="font-weight:bold; margin-bottom:8px;">Employee roles &amp; rates</p>

        @forelse ($rates as $index => $rate)
            <div class="rate-card">
                <h2>Role {{ $index + 1 }} — effective {{ $fmtDate($rate->effective_date) }}@if($rate->till_date) – {{ $fmtDate($rate->till_date) }}@endif</h2>
                <table class="grid">
                    <tr><th>Company</th><td>{{ $rate->company->name ?? '—' }}</td></tr>
                    <tr><th>Role</th><td>{{ $roleLabel($rate) }}</td></tr>
                    <tr><th>Pay type</th><td>{{ $rate->pay_type ?? '—' }}</td></tr>
                    <tr><th>Rate type</th><td>{{ $rate->rate_type ?? '—' }}</td></tr>
                    @if($showsPayrollType($rate->rate_type))
                        <tr><th>Payroll type</th><td>{{ $rate->payroll_type ?? '—' }}</td></tr>
                    @endif
                    @if($showsSlabFirst($rate->rate_type))
                        <tr><th>Slab first hours</th><td>{{ $rate->slab_first_hours ?? '—' }}</td></tr>
                    @endif
                    <tr><th>Rate</th><td>{{ $fmtMoney($rate->rate) }}</td></tr>
                    @if($showsSlabFirst($rate->rate_type))
                        <tr><th>Slab rest rate</th><td>{{ $fmtMoney($rate->slab_rest_rate) }}</td></tr>
                    @endif
                    @if($rate->rate_type === 'Payroll 1099')
                        <tr><th>Payroll rate</th><td>{{ $fmtMoney($rate->payroll_rate) }}</td></tr>
                    @endif
                    @if($rate->rate_type === '1099 1099')
                        <tr><th>1099 rate</th><td>{{ $fmtMoney($rate->ten99_rate) }}</td></tr>
                    @endif
                    @if($rate->rate_type === 'Payroll Slab')
                        <tr><th>Payroll hours</th><td>{{ $payrollHoursLabel($rate) }}</td></tr>
                    @endif
                    @if($showsCheckPayment($rate->rate_type))
                        <tr><th>Check payment</th><td>{{ $checkPaymentLabel($rate) }}</td></tr>
                    @endif
                </table>
                @if($rate->rate_type === 'Payroll Slab' && $rate->payroll_hours)
                    <p class="note"><strong>Note:</strong> Any hours worked beyond {{ $payrollHoursLabel($rate) }} will be paid as 1099.</p>
                @endif
                @if(in_array($rate->rate_type, ['1099 Regular', 'Payroll Regular'], true) && $rate->rate)
                    <p class="note"><strong>Note:</strong> Overtime will be calculated at 1.5× the regular rate ({{ $fmtMoney((float) $rate->rate * 1.5) }}).</p>
                @endif
            </div>
        @empty
            <p style="font-size:14px;color:#666;">No employee rate rows on file yet.</p>
        @endforelse
    </div>
</body>
</html>
