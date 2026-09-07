<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Print Cheque - Sample</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <style>
        @font-face {
            font-family: 'OpenSans-Bold';
            font-style: normal;
            font-weight: normal;
            src: url('/fonts/fonts1.ttf') format('truetype');
        }

        @font-face {
            font-family: 'MICR-Encoding';
            font-weight: 600;
            font-style: normal;
            src: url('/fonts/MicrEncoding-ZEDJ.ttf') format('truetype');
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
    <!-- Sample Cheque -->
    <main style="width:100%;height:1050px">
        <div style="margin-top: 5.2913385827px">
            <div class="row1">
                <div class="float-left" style="width: 330px">
                    <div class="text-bold" style="font-size:14px;">ABC COMPANY INC.
                    </div>

                    <div class="" style="line-height: 90%;">
                        456 Corporate Blvd <br>
                        San Francisco CA 94102
                    </div>

                </div>
                <div class="float-left text-center" style="width: 205px">
                    <div class="text-bold font-12">FIRST NATIONAL BANK</div>
                    <div class="font-10">123 Bank Street</div>
                    <div class="font-10">San Francisco, CA 94103</div>
                    <div class="font-10">123456789</div>
                </div>
                <div class="float-left text-left" style="width: 145px;margin-top:15px;">
                    <div class="text-bold" style="margin-left: 0px">1001</div>
                    <div class="font-15"><b>DATE:</b> 02/14/2026</div>
                </div>
                <div style="clear:both"></div>
            </div>
        </div>

        <div style="">
            <div class="row1">
                <div class="float-left" style="width: 85%;margin-top: 30px">
                    <span class="font-10"><b>PAY TO THE</b> <br><b>ORDER OF</b></span>
                    <span style="margin-left: 20px;position: relative;bottom:5px"  class="font-12">
                        <b>ACME CORPORATION</b> <br>
                    </span>
                </div>
                <div class="float-left" style="width: 13%;margin-left:22px;margin-top: 39px">
                    $ &nbsp;&nbsp;2,500.00
                </div>
                <div style="clear:both"></div>

            </div>
        </div>
        <div style="margin-top: 10px; width:100%;margin-left:15px">
            <div class="row1">
                <span class="font-12">
                    TWO THOUSAND FIVE HUNDRED DOLLARS AND 00/100
                </span>

            </div>
        </div>

        <div class="font-12" style="margin-left:55px;margin-top:20px;width:100%">
            <div style="height:14px;"><b>ACME CORPORATION</b></div>
            <div style="height:14px;">
                <b>123 BUSINESS STREET</b>, &nbsp;
                <b>SUITE 100</b> &nbsp;
            </div>
            <div style="height:14px;">
                <b>NEW YORK</b>,
                <b>NY</b>,
                <b>10001</b>
            </div>                
        </div>
        <div style="margin-top:20px;">
            <div class="row1 float-left">
                <span class="font-10">
                    <b>MEMO:</b>
                </span>
                <span class="font-12" style="position: relative;bottom:2px;margin-left:15px">
                    Invoice Payment - January 2026
                </span>
            </div>
            <div style="display: flex;flex-direction: column;position: relative;height: 1px">
                <hr class="float-right"
                    style="width: 30%; background-color: black; height: 0.5px; margin-right: 80px;">
            </div> 
            <div style="clear:both"></div>
        </div>



        <div style="padding-left: 17%;margin-top:15px">
            <div class="row1" style="font-family: MICR-Encoding;">
                <div class="micr text-bold"><b>C2001C A1231231A 1231231231C</b></div>
            </div>
        </div>



        <div style="padding-bottom: 0px;margin-top:82px;margin-left:20px">
            <div class="row1" style="margin: 0px;">
                <div class="float-left text-bold" style="width: 85%;">ABC COMPANY INC.</div>
                <div class="float-left text-right text-bold" style="width: 10%;">1001
                </div>
                <div style="clear: both;"></div>
            </div>
        </div>
        <div>
            <div class="row1" style="margin: 0px;margin-left:20px">
                <div class="float-left" style="width: 60%">
                    <span class="font-12 div2">NAME: &nbsp;&nbsp;&nbsp;&nbsp;</span>
                    <span class="font-14 div2">ACME CORPORATION</span>
                </div>
                <div class="float-left" style="width: 40%">
                    <span class="font-10">CHECK DATE:</span>
                    <span class="font-14">02/14/2026</span>
                </div>
                <div style="clear: both;"></div>

            </div>
        </div>

        <div class="border-1 radius-5 pt-2" style="height: 272.12598425px;width: 90%;padding: 10px;">
            <div class="row1" style="position: relative;width: 100%;height: 100%;">
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
                        <tr>
                            <td>01/15/2026</td>
                            <td>Bill</td>
                            <td>INV-2026-001</td>
                            <td class="text-right">1,500.00</td>
                            <td class="text-right">1,500.00</td>
                            <td class="text-right"></td>
                            <td class="text-right">1,500.00</td>
                        </tr>
                        <tr>
                            <td>01/20/2026</td>
                            <td>Bill</td>
                            <td>INV-2026-002</td>
                            <td class="text-right">1,000.00</td>
                            <td class="text-right">1,000.00</td>
                            <td class="text-right"></td>
                            <td class="text-right">1,000.00</td>
                        </tr>
                    </tbody>
                    <thead>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>Check Amount</td>
                            <td class="text-right">2,500.00</td>
                        </tr>
                    </thead>
                </table>
                <table class="font-14 pl-4 pr-5" style="position: absolute; bottom: 0px;">
                    <thead>
                        <tr>
                            <td colspan="6">FIRST NATIONAL BANK - Account #1234567890</td>
                            <td class="text-right">2,500.00</td>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>


        <div style="padding-bottom: 0px;margin-top:79px;margin-left:20px">
            <div class="row1" style="margin: 0px;">
                <div class="float-left text-bold" style="width: 85%;">ABC COMPANY INC.</div>
                <div class="float-left text-right text-bold" style="width: 10%;">1001
                </div>
                <div style="clear: both;"></div>
            </div>
        </div>
        <div>
            <div class="row1" style="margin: 0px;margin-left:20px">
                <div class="float-left" style="width: 60%">
                    <span class="font-12 div2">NAME: &nbsp;&nbsp;&nbsp;&nbsp;</span>
                    <span class="font-14 div2">ACME CORPORATION</span>
                </div>
                <div class="float-left" style="width: 40%">
                    <span class="font-10">CHECK DATE:</span>
                    <span class="font-14">02/14/2026</span>
                </div>
                <div style="clear: both;"></div>

            </div>
        </div>

        <div class="border-1 radius-5 pt-2" style="height: 272.12598425px;width: 90%;padding: 10px;">
            <div class="row1" style="position: relative;width: 100%;height: 100%;">
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
                        <tr>
                            <td>01/15/2026</td>
                            <td>Bill</td>
                            <td>INV-2026-001</td>
                            <td class="text-right">1,500.00</td>
                            <td class="text-right">1,500.00</td>
                            <td class="text-right"></td>
                            <td class="text-right">1,500.00</td>
                        </tr>
                        <tr>
                            <td>01/20/2026</td>
                            <td>Bill</td>
                            <td>INV-2026-002</td>
                            <td class="text-right">1,000.00</td>
                            <td class="text-right">1,000.00</td>
                            <td class="text-right"></td>
                            <td class="text-right">1,000.00</td>
                        </tr>
                    </tbody>
                    <thead>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td>Check Amount</td>
                            <td class="text-right">2,500.00</td>
                        </tr>
                    </thead>
                </table>
                <table class="font-14 pl-4 pr-5" style="position: absolute; bottom: 0;">
                    <thead>
                        <tr>
                            <td colspan="6">FIRST NATIONAL BANK - Account #1234567890</td>
                            <td class="text-right">2,500.00</td>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>

    </main>


</body>

</html>
