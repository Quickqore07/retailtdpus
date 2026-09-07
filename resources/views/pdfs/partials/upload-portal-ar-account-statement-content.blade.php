@php
    $fmtMoney = static fn ($n) => '$' . number_format((float) $n, 2, '.', ',');
    $fmtDate = static fn ($ymd) => $ymd ? \Carbon\Carbon::parse($ymd)->format('m/d/Y') : '—';
    $toLines = $customerToLines;
    $companyAddressLines = $companyAddressLines;
    $over90 = $balanceOver90DaysPastDue;
@endphp

<table class="header-table">
    <tr>
        <td width="58%">
            <p class="from-name">{{ $companyLegalName }}</p>
            <p class="from-line">{{ $companyAddressLines }}</p>
        </td>
        <td width="42%">
            <p class="statement-title">Statement</p>
            <div class="date-label-cell">
                <span class="date-label">Date</span>
            </div>
            <p class="statement-date">{{ $fmtDate($endDate ?? '') }}</p>
        </td>
    </tr>
</table>

<div class="to-wrap">
    <div class="to-label">TO:</div>
    <div class="to-lines">
        @foreach ($toLines as $line)
            <p>{{ $line }}</p>
        @endforeach
    </div>
</div>

<p class="table-note">This statement shows outstanding invoice balances and any customer credit on account.</p>

<table class="lines">
    <thead>
        <tr>
            <th class="date">Date</th>
            <th>Transaction</th>
            <th style="width: 78px; text-align: right;">Amount</th>
            <th style="width: 78px; text-align: right;">Balance</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($lines ?? [] as $line)
            <tr>
                <td class="date">{{ $fmtDate($line['date'] ?? '') }}</td>
                <td class="trans">{{ $line['transaction'] ?? '' }}</td>
                <td class="num">{{ $fmtMoney($line['amount'] ?? 0) }}</td>
                <td class="num">{{ $fmtMoney($line['balance'] ?? 0) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" style="text-align:center; padding: 12px;">No outstanding invoices or customer credit on account.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="meta-row">
    <strong>Period</strong> {{ $fmtDate($startDate ?? '') }} — {{ $fmtDate($endDate ?? '') }}
    &nbsp;·&nbsp;
    <strong>Balance due</strong> (through {{ $fmtDate($endDate ?? '') }}): {{ $fmtMoney($closingBalance ?? 0) }}
</div>

<table class="footer-table">
    <tr>
        <td class="footer-contact">
            If you have any questions regarding this statement, please contact your accounts representative.
        </td>
        <td class="summary-wrap" width="38%">
            <table class="summary">
                <tr>
                    <td class="label">Over 90 days past due</td>
                    <td class="value">{{ $fmtMoney($over90) }}</td>
                </tr>
                <tr>
                    <td class="label">Balance due</td>
                    <td class="value balance-due">{{ $fmtMoney($closingBalance ?? 0) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<p class="gen-line">Generated {{ $generatedAt ?? now()->format('m/d/Y g:i A') }}</p>
