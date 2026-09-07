<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoiceNumber }}</title>
    <style>
        @page {
            margin: 28px 36px 32px 36px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            font-size: 10px;
            color: #111827;
            margin: 0;
            padding: 0;
            line-height: 1.45;
        }

        .sheet {
            max-width: 100%;
        }

        /* Header — matches modal: flex row + border-b-2 border-gray-900 */
        .hdr {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #111827;
            margin-bottom: 0;
        }

        .hdr td {
            vertical-align: top;
            padding: 0 0 18px 0;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #111827;
            margin: 0 0 4px 0;
        }

        .company-addr {
            font-size: 10px;
            color: #374151;
            margin: 0;
            max-width: 260px;
            line-height: 1.45;
        }

        .hdr-right {
            text-align: right;
            width: 42%;
        }

        .invoice-title {
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 0.06em;
            color: #111827;
            margin: 0 0 4px 0;
        }

        .invoice-no {
            font-size: 10px;
            color: #4b5563;
            margin: 0;
        }

        .invoice-no strong {
            color: #111827;
            font-weight: 600;
        }

        /* Meta — Bill To + date grid */
        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .meta td {
            vertical-align: top;
            padding: 0;
        }

        .bill-label {
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #6b7280;
            margin: 0 0 8px 0;
        }

        .bill-name {
            font-size: 11px;
            font-weight: bold;
            color: #111827;
            margin: 0 0 4px 0;
        }

        .bill-line {
            font-size: 10px;
            color: #374151;
            margin: 0 0 3px 0;
        }

        .bill-line.mt {
            margin-top: 8px;
        }

        .bill-line strong {
            color: #111827;
            font-weight: 600;
        }

        .dates-wrap {
            text-align: right;
        }

        .dates {
            border-collapse: collapse;
        }

        .dates td {
            padding: 0 0 8px 0;
            vertical-align: top;
            font-size: 10px;
        }

        .dates td.lbl {
            color: #6b7280;
            padding-right: 20px;
            white-space: nowrap;
        }

        .dates td.val {
            color: #111827;
            font-weight: 600;
            text-align: right;
        }

        .dates tr.balance-due td.val {
            font-weight: 700;
        }

        /* Items — bg-gray-200 header, border-b rows */
        .items-wrap {
            margin-top: 26px;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .items thead th {
            background: #e5e7eb;
            color: #111827;
            text-align: left;
            font-weight: bold;
            padding: 8px 8px;
            border: none;
        }

        .items thead th.num {
            text-align: right;
        }

        .items thead th.w-num {
            width: 4%;
        }

        .items thead th.w-qty {
            width: 8%;
        }

        .items thead th.w-unit {
            width: 11%;
        }

        .items thead th.w-disc {
            width: 10%;
        }

        .items thead th.w-tax {
            width: 10%;
        }

        .items thead th.w-amt {
            width: 12%;
        }

        .items tbody td {
            padding: 8px 8px;
            border: none;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .items tbody td.idx {
            color: #374151;
            text-align: left;
        }

        .items tbody td.desc {
            color: #111827;
        }

        .items tbody td.num {
            text-align: right;
            color: #374151;
        }

        .items tbody td.amt {
            text-align: right;
            color: #111827;
            font-weight: 600;
        }

        /* Totals block — compact table, flush right (PDF engines often ignore margin:auto on tables) */
        .totals-outer {
            margin-top: 20px;
            width: 100%;
            text-align: right;
        }

        .totals-outer > table {
            display: inline-table;
            width: auto;
            min-width: 200px;
            max-width: 280px;
            border-collapse: collapse;
            text-align: left;
        }

        .totals-outer td {
            font-size: 10px;
            color: #374151;
            padding: 5px 0;
            vertical-align: middle;
        }

        .totals-outer td.lbl {
            text-align: left;
        }

        .totals-outer td.amt {
            text-align: right;
        }

        .totals-outer tr.tax-row td {
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 8px;
        }

        .totals-outer tr.total-row td {
            font-size: 11px;
            font-weight: bold;
            color: #111827;
            padding-top: 8px;
            padding-bottom: 4px;
        }

        .payment-row td {
            font-size: 10px;
            color: #374151;
            padding: 5px 0;
        }

        .amount-due-bar {
            background: #e5e7eb;
            color: #111827;
            margin-top: 6px;
            padding: 8px 10px;
            border-radius: 2px;
        }

        .amount-due-bar table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }

        .amount-due-bar td {
            padding: 0;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .amount-due-bar td.amt {
            text-align: right;
            font-size: 12px;
            font-weight: bold;
            text-transform: none;
            letter-spacing: normal;
        }

        .remarks {
            margin-top: 26px;
            padding-top: 14px;
            border-top: 1px solid #e5e7eb;
        }

        .remarks .lbl {
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #6b7280;
            margin: 0 0 4px 0;
        }

        .remarks .txt {
            margin: 0;
            font-size: 10px;
            color: #374151;
            white-space: pre-wrap;
        }
    </style>
</head>

<body>
    <div class="sheet">
        <table class="hdr">
            <tr>
                <td>
                    <p class="company-name">{{ $companyLegalName }}</p>
                    <p class="company-addr">{{ $companyAddressLines }}</p>
                </td>
                <td class="hdr-right">
                    <p class="invoice-title">INVOICE</p>
                    <p class="invoice-no"><strong>No:</strong> {{ $invoiceNumber }}</p>
                </td>
            </tr>
        </table>

        <table class="meta">
            <tr>
                <td>
                    <p class="bill-label">Bill To</p>
                    <p class="bill-name">{{ $customerName }}</p>
                    @if ($customerPrimaryPerson !== '')
                        <p class="bill-line">Attn: {{ $customerPrimaryPerson }}</p>
                    @endif
                    @if ($customerAddressLine1 !== '')
                        <p class="bill-line">{{ $customerAddressLine1 }}</p>
                    @endif
                    @if ($customerAddressLine2 !== '')
                        <p class="bill-line">{{ $customerAddressLine2 }}</p>
                    @endif
                    @if ($customerAddressLine3 !== '')
                        <p class="bill-line">{{ $customerAddressLine3 }}</p>
                    @endif
                    @if ($customerCityStateZip !== '')
                        <p class="bill-line">{{ $customerCityStateZip }}</p>
                    @endif
                    @if ($customerCountry !== '')
                        <p class="bill-line">{{ $customerCountry }}</p>
                    @endif
                    {{-- @if ($customerEmail !== '')
                        <p class="bill-line mt"><strong>Email:</strong> {{ $customerEmail }}</p>
                    @endif
                    @if ($customerMobile !== '')
                        <p class="bill-line"><strong>Phone:</strong> {{ $customerMobile }}</p>
                    @endif --}}
                </td>
                <td class="dates-wrap" style="width: 38%;">
                    <table class="dates" align="right">
                        <tr>
                            <td class="lbl">Invoice Date</td>
                            <td class="val">{{ $invoiceDate }}</td>
                        </tr>
                        <tr>
                            <td class="lbl">Due Date</td>
                            <td class="val">{{ $dueDate }}</td>
                        </tr>
                        <tr class="balance-due">
                            <td class="lbl">Balance Due</td>
                            <td class="val">{{ $balanceDue }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="items-wrap">
            <table class="items">
                <thead>
                    <tr>
                        <th class="w-num">#</th>
                        <th>Description</th>
                        <th class="num w-qty">Qty</th>
                        <th class="num w-unit">Unit Price</th>
                        <th class="num w-disc">Discount</th>
                        <th class="num w-tax">Tax</th>
                        <th class="num w-amt">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($items as $idx => $row)
                        <tr>
                            <td class="idx">{{ $idx + 1 }}</td>
                            <td class="desc">{{ $row['item_name'] }}</td>
                            <td class="num">{{ $row['qty'] }}</td>
                            <td class="num">{{ $row['unit_price'] }}</td>
                            <td class="num">{{ $row['discount'] }}</td>
                            <td class="num">{{ $row['tax'] }}</td>
                            <td class="amt">{{ $row['total'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;color:#6b7280;padding:20px 8px;">No line items.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="totals-outer">
            <table align="right">
                <tr>
                    <td class="lbl">Subtotal</td>
                    <td class="amt">{{ $subtotal }}</td>
                </tr>
                <tr>
                    <td class="lbl">Discount</td>
                    <td class="amt">-{{ $discountTotal }}</td>
                </tr>
                <tr class="tax-row">
                    <td class="lbl">Tax</td>
                    <td class="amt">+{{ $taxTotal }}</td>
                </tr>
                <tr class="total-row">
                    <td class="lbl">Total</td>
                    <td class="amt">{{ $amountTotal }}</td>
                </tr>
                @foreach (($paymentSummary ?? []) as $pay)
                    <tr class="payment-row">
                        <td class="lbl" style="padding-right:8px;">{{ $pay['label'] }}</td>
                        <td class="amt">{{ $pay['amount'] }}</td>
                    </tr>
                @endforeach
            </table>
        </div>

        @if ($remarks !== '')
            <div class="remarks">
                <p class="lbl">Notes</p>
                <p class="txt">{{ $remarks }}</p>
            </div>
        @endif
    </div>
</body>

</html>
