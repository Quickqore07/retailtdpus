<html lang="en">

<head>
    <!--  HEAD    -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Onboarding Process Link</title>

    <style>
        body {
            font-family: GoogleSans-Regular;
            padding: 20px;
            background: #f1f1f1;
        }

        .container {
            background-color: #b1151d;
            width: 80%;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .inner_container {
            background-color: #ffffff;
            padding: 30px 30px 10px 30px;
        }

        header,
        footer {
            text-align: center;
        }

        .email_inner_section {
            padding: 20px 0 50px 0;
        }

        hr {
            height: 5px;
            background-color: brown;
            border-color: brown;
        }

        h1 {
            color: brown;
        }

        .enquiry_submission table {
            text-align: left;
            margin-top: 50px;
        }

        .enquiry_submission table tbody tr th {
            width: 30%;
            vertical-align: top;
        }

        .enquiry_submission th,
        .enquiry_submission td {
            padding: 10px;
            margin: 0;
        }

        .enquiry_submission th {
            color: #b1151d;
            font-weight: 900;
        }

        .enquiry_submission td {
            font-weight: 100;
        }

        .email_footer {
            font-size: 10px;
            color: #ffffff;
            padding: 20px 0;
        }

        .email_footer a {
            color: #ffffff;
            text-decoration: none;
        }

        @media only screen and (max-width:500px) {

            .enquiry_submission th,
            .enquiry_submission td {
                display: block;
                width: 100% !important;
            }
        }
    </style>

</head>

<!-- BODY   -->

<body style="background: #f1f1f1;padding: 20px;">
    <div class="container">
        <div class="inner_container">
            <header>
                {{-- <img src="https://pj.quickob.com/public/backend/assets/img/pj-logo.png" alt="Papa John"
                    style="width: 100%"> --}}
                <h3
                    style="background: #b1151d;color: #fff;margin-block-start: 0.83em;margin-block-end: 0.83em;padding: 15px 0;text-align: center;">
                    Onboarding Process Link</h3>
            </header>
            <div class="email_content">
                <div class="email_inner_section1">
                    <p>Hi,</p>
                    <p>I hope this message finds you well. I am pleased to inform you that you have been selected to
                        proceed to the onboarding process for <strong>{{ $storeName }}</strong>. Your skills and
                        experience stood out to us, and we believe you will be a valuable addition to our team.</p>
                    <p>To facilitate a smooth transition into your new role, please follow the steps outlined below:</p>
                    <p><strong>Onboarding Link:</strong> Click on the following link to access our onboarding portal: <a
                            href="{{ $onboardingLink }}" target="_blank">Click here</a></p>
                    <p style="margin: 0;"><strong>Onboarding Code:</strong> Your unique onboarding code is:
                        <strong>{{ $onboardingCode }}</strong>.
                    </p>
                    <p style="margin: 0;">Please use this code when prompted during the further process.</p>
                    <p><strong>Documents:</strong> Complete the necessary documents available on the onboarding portal.
                        These may include Form I9, Form W4, Emergency Contact Number, Enrollment Form and other
                        relevant paperwork.</p>
                    <p><strong>Background Check:</strong> Our HR team will initiate a background check process. Please
                        provide any additional information or documentation required promptly.</p>
                    <p style="margin-bottom:15px;">Once again, congratulations on your selection! We look forward to
                        welcoming you aboard and working together at <strong>{{ $storeName }}</strong>.</p>
                    <p>Best regards,</p>

                    <p style="margin:0;"><strong>{{ $storeName }}</strong></p>
                </div>
            </div>
        </div>
        <!--   Footer     -->
        <footer style="font-size: 12px;color: #ffffff;padding: 5px;">
            <a href="{{ url('/') }}" style="color: #fff;text-decoration: none;">www.mypjpizza.com</a>
            <p style="margin-bottom: 0;">Copyright &copy;
                <script>
                    document.write(new Date().getFullYear())
                </script> <a href="{{ url('/') }}" style="color: #fff;">tdpus</a> All Rights
                Reserved
            </p>
        </footer>
        <!--   footer ends     -->
    </div>
</body>

</html>
