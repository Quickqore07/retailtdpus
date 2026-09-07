<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Authorization For Direct Deposit</title>
    <style>
        @page {
            margin: 40px;
        }

        body {
            margin: 0;
            padding: 0;
            font-size: 10px;
            line-height: 13px;
        }

        .dd-field {
            min-height: 12px;
            border-bottom: 1px solid #000;
            margin-bottom: 2px;
        }

        .dd-field p {
            margin: 2px 0 0 0;
            font-size: 12px;
            line-height: 13px;
        }

        .dd-radio {
            display: inline-block;
            width: 8px;
            height: 8px;
            border: 1px solid #000;
            border-radius: 50%;
            vertical-align: middle;
            margin-right: 2px;
        }

        .dd-radio.filled {
            background: #000;
        }
    </style>
</head>

<body>
@php
    $directDepositData = $directDepositData ?? null;
    $storeData = $storeData ?? null;
    $employeeProfile = $employeeProfile ?? null;
    $companyName = $storeData->company_name ?? ($company->name ?? '');
    $formatDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('m-d-Y') : '';
    $printedName = $directDepositData->printed_name ?? trim(($employeeProfile->cp_firstName ?? '') . ' ' . ($employeeProfile->cp_middleInitial ?? '') . ' ' . ($employeeProfile->cp_lastName ?? ''));
    $signature = $directDepositData->signature ?? $printedName;
@endphp

<div class="inner direct-deposit-page">
    <h5 style="font-size: 18px; text-align: center; margin: 0 0 15px 0;">Authorization For Direct Deposit - Employee Form</h5>

    <table style="border-collapse: collapse; width: 100%; font-size: 12px; line-height: 15px;border: 0px solid transparent;">
        <tr>
            <td style="padding: 4px 8px; border: 1px solid transparent;" colspan="2">
                <span style="vertical-align: bottom;">This authorizes</span>
                <span class="dd-field" style="display: inline-block; min-width: 120px; vertical-align: bottom; margin-top: 5px;"><p>{{ $companyName }}</p></span>
                <span style="vertical-align: bottom;">(the "Company") to send credit entries (and appropriate debit and adjustment entries), electronically or by any other commercially accepted method, to my (our) account(s) indicated below and to other accounts I (we) identify in the future (the "Account"). This authorizes the financial institution holding the Account to post all such entries.</span>
                <p style="margin: 8px 0 0 0;"><b>Note: Enter your company name in the blank space above.</b></p>
            </td>
        </tr>
        <tr>
            <td style="padding: 4px 8px; border: 1px solid transparent;margin-top: 10px;" width="50%">
                <p style="margin: 0 0 5px 0;"><b>Account #1</b></p>
                <p style="margin: 0 0 8px 0; font-size: 10px;">Account #1 Type (check one)
                    <span class="dd-radio {{ ($directDepositData->acct_type_1 ?? '') == 'checking' ? 'filled' : '' }}"></span> Checking
                    <span class="dd-radio {{ ($directDepositData->acct_type_1 ?? '') == 'savings' ? 'filled' : '' }}"></span> Savings
                </p>
                <div class="dd-field"><p>{{ $directDepositData->bank_name_1 ?? '' }}</p></div>
                <label style="font-size: 9px;">Employee Bank Name</label>
            </td>
            <td style="padding: 4px 8px; border: 1px solid transparent;" width="50%">
                <p style="margin: 0 0 5px 0; opacity: 0;"><b>.</b></p>
                <p style="margin: 0 0 8px 0; opacity: 0;">. Checking Savings</p>
                <div class="dd-field"><p>{{ $directDepositData->bank_routing_1 ?? '' }}</p></div>
                <label style="font-size: 9px;">Bank routing # (ABA#)</label>
            </td>
        </tr>
        <tr>
            <td style="padding: 4px 8px; border: 1px solid transparent;">
                <div class="dd-field"><p>{{ $directDepositData->account_number_1 ?? '' }}</p></div>
                <label style="font-size: 9px;">Account #</label>
            </td>
            <td style="padding: 4px 8px; border: 1px solid transparent;">
                <div class="dd-field"><p>{{ $directDepositData->deposit_amount_1 ?? '' }} {{ $directDepositData->deposit_type_1 === 'percentage' && $directDepositData->deposit_amount_1 ? '%' : ($directDepositData->deposit_amount_1 ? '$' : '') }}</p></div>
                <label style="font-size: 9px;">Percentage or Dollar Amount to be Deposited to This Account</label>
            </td>
        </tr>
        <tr>
            <td style="padding: 4px 8px; border: 1px solid transparent;margin-top: 10px;" width="50%">
                <p style="margin: 0 0 5px 0;"><b>Account #2</b></p>
                <p style="margin: 0 0 8px 0; font-size: 10px;">Account #2 Type (check one)
                    <span class="dd-radio {{ ($directDepositData->acct_type_2 ?? '') == 'checking' ? 'filled' : '' }}"></span> Checking
                    <span class="dd-radio {{ ($directDepositData->acct_type_2 ?? '') == 'savings' ? 'filled' : '' }}"></span> Savings
                </p>
                <div class="dd-field"><p>{{ $directDepositData->bank_name_2 ?? '' }}</p></div>
                <label style="font-size: 9px;">Employee Bank Name</label>
            </td>
            <td style="padding: 4px 8px; border: 1px solid transparent;" width="50%">
                <p style="margin: 0 0 5px 0; opacity: 0;"><b>.</b></p>
                <p style="margin: 0 0 8px 0; opacity: 0;">. Checking Savings</p>
                <div class="dd-field"><p>{{ $directDepositData->bank_routing_2 ?? '' }}</p></div>
                <label style="font-size: 9px;">Bank routing # (ABA#)</label>
            </td>
        </tr>
        <tr>
            <td style="padding: 4px 8px; border: 1px solid transparent;">
                <div class="dd-field"><p>{{ $directDepositData->account_number_2 ?? '' }}</p></div>
                <label style="font-size: 9px;">Account #</label>
            </td>
            <td style="padding: 4px 8px; border: 1px solid transparent;">
                <div class="dd-field"><p>{{ $directDepositData->deposit_amount_2 ?? '' }} {{ $directDepositData->deposit_type_2 === 'percentage' && $directDepositData->deposit_amount_2 ? '%' : ($directDepositData->deposit_amount_2 ? '$' : '') }}</p></div>
                <label style="font-size: 9px;">Percentage or Dollar Amount to be Deposited to This Account</label>
            </td>
        </tr>
        <tr>
            <td style="padding: 4px 8px; border: 1px solid transparent;" colspan="2">
                <label style="font-size: 10px;">This authorization will be in effect until the Company receives a written termination notice from myself and has a reasonable opportunity to act on it.</label>
            </td>
        </tr>
        <tr>
            <td style="padding: 4px 8px; border: 1px solid transparent;" width="50%">
                <div class="dd-field"><p>{{ $signature }}</p></div>
                <label style="font-size: 9px;">Signature</label>
            </td>
            <td style="padding: 4px 8px; border: 1px solid transparent;" width="50%">
                <div class="dd-field"><p>{{ $printedName }}</p></div>
                <label style="font-size: 9px;">Printed Name</label>
            </td>
        </tr>
        <tr>
            <td style="padding: 4px 8px; border: 1px solid transparent;" width="50%">
                <div class="dd-field"><p>{{ $employeeProfile->cp_posId ?? '' }}</p></div>
                <label style="font-size: 9px;">Employee ID #</label>
            </td>
            <td style="padding: 4px 8px; border: 1px solid transparent;" width="50%">
                <div class="dd-field"><p>{{ $directDepositData->date ? $formatDate($directDepositData->date) : '' }}</p></div>
                <label style="font-size: 9px;">Date</label>
            </td>
        </tr>
    </table>
</div>

</body>

</html>
