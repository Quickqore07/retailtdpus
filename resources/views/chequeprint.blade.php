<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Print Cheque</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <style>
        @font-face {
            font-family: 'OpenSans-Bold';
            font-style: normal;
            font-weight: normal;
            src: url(public_path(). '/fonts/fonts1.ttf') format('truetype');
        }

        @font-face {
            font-family: 'MICR-Encoding';
            font-weight: 600;
            font-style: normal;
            src: url({{ storage_path('/fonts/MicrEncoding-ZEDJ.ttf') }}) format('truetype');
        }

        * {
            margin: 0px;
            padding: 0px;
            line-height: 96%;
        }

        .micr {
            font-size: 25px !important;
            font-family: 'MICR-Encoding', monospace !important;
        }

        main {
            padding: 29px;
        }

        table th,
        td {
            padding-bottom: 4px
        }

        /* Embedded styles from style.css */
        main > *{
            width: 100%;
        }
        table {
            width: 100%;
        }
        .text-bold{
            font-weight: 700;
            color: #000;
        }
        .text-right{
            text-align: right;
        }
        .text-left{
            text-align: left;
        }
        .font-10{
            font-size: 10px;
        }
        .font-11 {
            font-size: 11px;
        }
        .font-12{
            font-size: 12px;
        }
        .font-15{
            font-size: 14px;
        }
        .font-14{
            font-size: 14px;
        }
        .font-16{
            font-size: 16px;
        }
        .font-18{
            font-size: 18px;
            color: #000;
        }
        .border-bottom{
            border-bottom: 2px solid #000 !important;
        }
        .border-1{
            border: 1px solid #000;
        }
        .radius-5{
            border-radius: 5px;
        }
        .container1{
            position: relative;
            height: 115px;
        }
        .container2{
            position: relative;
            height: 40px;
            margin: 0;
            padding:0;
        }
        .container21{
            position: relative;
            height: 25px;
            margin: 0;
            padding:0;
        }
        .container3{
            position: relative;
            height: 25px;
        }
        .container4{
            position: relative;
            height: 80px;
        }
        .container5{
            position: relative;
            height: 25px;
        }
        .container6{
            position: relative;
            height: 16px;
        }
        .container7{
            position: relative;
            height: 65px;
            width: 100%;
        }
        .container8{
            position: relative;
            height: 16px;
        }
        .container10{
            position: relative;
            height: 5px;
        }
        .container9{
            position: relative;
            height: 300px;
        }
        .row1{
            display: inline-block;
            width: 90%;
        }
        .div2{
            margin-top:-8px;
            padding: -8px;
        }
        .box2{
            margin-top: -52px;
        }
        .container12{
            margin-top: -1px;
        }
        .container13{
            width: 90%;
            position: relative;
            height: 40px;
            margin: 0px !important;
            padding-bottom: 0px !important;
        }
        .container14{
            width: 90%;
            position: relative;
            height: 0px;
            margin-top: 0px !important;
            padding: 0px !important;
        }
        .container15{
            position: relative;
            height: 60px;
        }
        .container16{
            position: relative;
            height: 18px;
        }
        .div3{
            margin-top:0px;
            padding-top: 0px;
        }
        .box3{
            margin-top: 0px;
        }
        .box4{
            margin-top: -8px;
        }
        .float-left{
            float: left;
        }
        .float-right{
            float: right;
        }
        .clear-both{
            clear: both;
        }
        .text-center{
            text-align: center;
        }
        .pt-2{
            padding-top: 0.5rem;
        }
        .pl-4{
            padding-left: 1rem;
        }
        .pr-5{
            padding-right: 1.25rem;
        }
    </style>
</head>

<body>
    @foreach ($chequeData as $paymentData)
        @php
            //$caddress = explode(',', $paymentData['companyaddress']);
            $baddress = explode(',', $paymentData['bankaddress']);
            // $vaddress2 = explode(',', $paymentData['vendoraddress2']);
        @endphp

        <main style="width:100%;height:1050px">
            <div style="margin-top: 5.2913385827px">
                <div class="row1">
                    <div class="float-left" style="width: 330px">
                        <div class="text-bold" style="font-size:14px;">{{ $paymentData['companyname'] }}
                        </div>

                        <div class="" style="line-height: 90%;">
                            {{ $paymentData['companyaddress'] }} <br>
                            {{ $paymentData['companycity'] }} {{ $paymentData['companystate'] }}
                            {{ $paymentData['companyzipcode'] }}
                        </div>

                    </div>
                    <div class="float-left text-center" style="width: 205px">
                        <div class="text-bold font-12">{{ strtoupper($paymentData['bankname']) }}</div>
                        @foreach ($baddress as $b)
                            <div class="font-10">{{ nl2br($b) }}</div>
                        @endforeach
                        <div class="font-10">{{ $paymentData['transitcode'] }} </div>
                    </div>
                    <div class="float-left text-left" style="width: 145px;margin-top:15px;">
                        <div class="text-bold" style="margin-left: 0px">{{ $paymentData['cheque'] }}</div>
                        <div class="font-15"><b>DATE:</b> {{ mysql2dmy($paymentData['date']) }}</div>
                    </div>
                    <div style="clear:both"></div>
                </div>
            </div>

            <div style="">
                <div class="row1">
                    <div class="float-left" style="width: 85%;margin-top: 30px">
                        <span class="font-10"><b>PAY TO THE</b> <br><b>ORDER OF</b></span>
                        <span style="margin-left: 20px;position: relative;bottom:5px"  class="font-12">
                            <b>{{ strtoupper($paymentData['vendorname']) }}</b> <br>
                        </span>
                    </div>
                    <div class="float-left" style="width: 13%;margin-left:22px;margin-top: 39px">
                        $ &nbsp;&nbsp;{{ $paymentData['chequeamount'] }}
                    </div>
                    <div style="clear:both"></div>

                </div>
            </div>
            <div style="margin-top: 10px; width:100%;margin-left:15px">
                <div class="row1" >
                    <span class="font-12 "  >
                        {{ $paymentData['chequeamountwords'] }}
                    </span>
                </div>
            </div>

            <div class="font-12" style="margin-left:55px;margin-top:20px;width:100%">
                <div style="height:14px;"><b>{{ strtoupper($paymentData['vendorname']) }}</b></div>
                <div style="height:14px;">
                @if ($paymentData['vendoraddress1'] != '')
                    <b>{{ $paymentData['vendoraddress1'] }}</b>, &nbsp;
                @endif
                @if ($paymentData['vendoraddress2'] != '')
                    <b>{{ $paymentData['vendoraddress2'] }}</b> &nbsp;
                @endif
                </div>
                <div style="height:14px;">
                @if ($paymentData['vendorcity'] != '')
                <b>{{ $paymentData['vendorcity'] }}</b>,
                @endif
                @if ($paymentData['vendorstate'] != '')
                <b>{{ $paymentData['vendorstate'] }}</b>,
                @endif
                @if ($paymentData['vendorzipcode'] != '')
                <b>{{ $paymentData['vendorzipcode'] }}</b>
                @endif
                </div>                
            </div>
            <div style="margin-top:20px;">
                <div class="row1 float-left">
                    <span class="font-10">
                        <b>MEMO:</b>
                    </span>
                    <span class="font-12" style="position: relative;bottom:2px;margin-left:15px">
                        {{ $paymentData['memo'] }}
                    </span>
                </div>
                <div style="display: flex;flex-direction: column;position: relative;height: 1px">
                    {{-- <div class="float-right">
                        @if ($signatureResult)
                            @php
                                $signaturePath = public_path($signatureResult['path']);
                                $signatureBase64 = '';
                                if (file_exists($signaturePath)) {
                                    $imageData = base64_encode(file_get_contents($signaturePath));
                                    $imageType = pathinfo($signaturePath, PATHINFO_EXTENSION);
                                    $signatureBase64 = 'data:image/' . $imageType . ';base64,' . $imageData;
                                }
                            @endphp
                            @if ($signatureBase64)
                                <div  style="position: absolute;bottom: -10px;right: 874px;">
                                    <img src="{{ $signatureBase64 }}" alt="Signature" style="height: 50px;">
                                </div>
                            @endif
                        @endif
                    </div> --}}
                    <hr class="float-right"
                        style="width: 30%; background-color: black; height: 0.5px; margin-right: 80px;">
                </div> 
                <div style="clear:both"></div>
            </div>



            <div style="padding-left: 17%;margin-top:15px">
                <div class="row1">
                    <div class="micr text-bold">
                        <b><img src="{{ $paymentData['chequeimage'] }}" style="height:70px"></b>
                    </div>
                </div>
            </div>



            <div style="padding-bottom: 0px;margin-top:30px;margin-left:20px">
                <div class="row1" style="margin: 0px;">
                    <div class="float-left text-bold" style="width: 85%;">{{ $paymentData['companyname'] }}</div>
                    <div class="float-left text-right text-bold" style="width: 10%;">{{ $paymentData['cheque'] }}
                    </div>
                    <div style="clear: both;"></div>
                </div>
            </div>
            <div>
                <div class="row1" style="margin: 0px;margin-left:20px">
                    <div class="float-left" style="width: 60%">
                        <span class="font-12 div2">NAME: &nbsp;&nbsp;&nbsp;&nbsp;</span>
                        <span class="font-14 div2">{{ strtoupper($paymentData['vendorname']) }}</span>
                    </div>
                    <div class="float-left" style="width: 40%">
                        <span class="font-10">CHECK DATE:</span>
                        <span class="font-14">{{ mysql2dmy($paymentData['date']) }}</span>
                    </div>
                    <div style="clear: both;"></div>

                </div>
            </div>

            <div class="border-1 radius-5 pt-2" style="height: 272.12598425px;width: 90%">
                <div class="row1" style="position: relative;width: 100%">
                    <table class="font-14 pl-4 pr-5">
                        <thead>
                            <tr>
                                <td>Date</td>
                                <td>Type</td>
                                <td>Reference</td>
                                <td class="text-right">Original Amt.</td>
                                <td class="text-right">Balance Due</td>
                                <td class="text-right">Discount</td>
                                <td class="text-right">Payment</td>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($paymentData['bills'] as $bill)
                                <tr>
                                    <td>{{ mysql2dmy($bill['date']) }}</td>
                                    {{-- <td>{{ isset($bill['type']) ? $bill['type'] : 'Bill' }}</td> --}}
                                    <td>-</td>
                                    {{-- <td>{{ $bill['reference'] }}</td> --}}
                                    <td>-</td>
                                    <td class="text-right">
                                        {{ isset($bill['type']) && $bill['type'] == 'Credit' ? -1 * $bill['amount'] : $bill['amount'] }}
                                    </td>
                                    <td class="text-right">
                                        {{ isset($bill['dueamount']) ? (isset($bill['type']) && $bill['type'] == 'Credit' ? -1 * $bill['dueamount'] : $bill['dueamount']) : 0 }}
                                    </td>
                                    <td class="text-right"></td>
                                    <td class="text-right">
                                        {{ isset($bill['dueamount']) ? (isset($bill['type']) && $bill['type'] == 'Credit' ? -1 * $bill['payment'] : $bill['payment']) : 0 }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <thead>
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td>Check Amount</td>
                                <td class="text-right">{{ $paymentData['chequeamount'] }}</td>
                            </tr>
                        </thead>
                    </table>
                    <table class="font-14 pl-4 pr-5" style="position: absolute; bottom: 20;">
                        <thead>
                            <tr>
                                <td colspan="6">{{ $paymentData['bankname'] }}</td>
                                <td class="text-right">{{ $paymentData['chequeamount'] }}</td>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>


            <div style="padding-bottom: 0px;margin-top:79px;margin-left:20px">
                <div class="row1" style="margin: 0px;">
                    <div class="float-left text-bold" style="width: 85%;">{{ $paymentData['companyname'] }}</div>
                    <div class="float-left text-right text-bold" style="width: 10%;">{{ $paymentData['cheque'] }}
                    </div>
                    <div style="clear: both;"></div>
                </div>
            </div>
            <div>
                <div class="row1" style="margin: 0px;margin-left:20px">
                    <div class="float-left" style="width: 60%">
                        <span class="font-12 div2">NAME: &nbsp;&nbsp;&nbsp;&nbsp;</span>
                        <span class="font-14 div2">{{ strtoupper($paymentData['vendorname']) }}</span>
                    </div>
                    <div class="float-left" style="width: 40%">
                        <span class="font-10">CHECK DATE:</span>
                        <span class="font-14">{{ mysql2dmy($paymentData['date']) }}</span>
                    </div>
                    <div style="clear: both;"></div>

                </div>
            </div>

            <div class="border-1 radius-5 pt-2" style="height: 272.12598425px;width: 90%">
                <div class="row1" style="position: relative;width: 100%">
                    <table class="font-14 pl-4 pr-5">
                        <thead>
                            <tr>
                                <td>Date</td>
                                <td>Type</td>
                                <td>Reference</td>
                                <td class="text-right">Original Amt.</td>
                                <td class="text-right">Balance Due</td>
                                <td class="text-right">Discount</td>
                                <td class="text-right">Payment</td>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($paymentData['bills'] as $bill)
                                <tr>
                                    <td>{{ mysql2dmy($bill['date']) }}</td>
                                    {{-- <td>{{ isset($bill['type']) ? $bill['type'] : 'Bill' }}</td> --}}
                                    <td>-</td>
                                    {{-- <td>{{ $bill['billnumber'] }}</td> --}}
                                    <td>-</td>
                                    <td class="text-right">
                                        {{ isset($bill['type']) && $bill['type'] == 'Credit' ? -1 * $bill['amount'] : $bill['amount'] }}
                                    </td>
                                    <td class="text-right">
                                        {{ isset($bill['dueamount']) ? (isset($bill['type']) && $bill['type'] == 'Credit' ? -1 * $bill['dueamount'] : $bill['dueamount']) : 0 }}
                                    </td>
                                    <td class="text-right"></td>
                                    <td class="text-right">
                                        {{ isset($bill['dueamount']) ? (isset($bill['type']) && $bill['type'] == 'Credit' ? -1 * $bill['payment'] : $bill['payment']) : 0 }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <thead>
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td>Check Amount</td>
                                <td class="text-right">{{ $paymentData['chequeamount'] }}</td>
                            </tr>
                        </thead>
                    </table>
                    <table class="font-14 pl-4 pr-5" style="position: absolute; bottom: 20;">
                        <thead>
                            <tr>
                                <td colspan="6">{{ $paymentData['bankname'] }}</td>
                                <td class="text-right">{{ $paymentData['chequeamount'] }}</td>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </main>
    @endforeach


</body>

</html>
