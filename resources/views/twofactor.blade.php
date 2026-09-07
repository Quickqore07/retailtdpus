<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TDPUS - 2FA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body,
        html {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f0f4f8;
            height: 100%;
            margin: 0;
            padding: 0;
            background-image: url('/images/authentication.webp');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center center;
            background-attachment: fixed;

        }

        .custom-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .custom-container-main {
            width: 100%;
            max-width: 600px;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }


        .custom-login-brand img {
            max-width: 300px;
            margin-bottom: 20px;
            margin: auto
        }

        .custom-login-brand {
            text-align: center
        }

        .custom-panel-body h1 {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }

        .custom-panel-body p {
            font-size: 16px;
            color: #555;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .custom-panel-body p b {
            color: #000;
        }

        .twofa-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        /* .custom-panel-body .btn-primary {
            display: inline-block;
            background-color: #3aa3e3;
            color: #fff;
            padding: 12px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 16px;
            margin: 10px;
            transition: background-color 0.3s ease;
        } */

        /* .custom-panel-body .btn-primary:hover {
            background-color: #2a93d3;
        } */

        /* Responsive Design */
        @media (max-width: 768px) {
            .custom-container-main {
                padding: 20px;
            }

            .custom-panel-body h1 {
                font-size: 24px;
            }

            .custom-panel-body p {
                font-size: 14px;
            }
        }
    </style>
</head>

<body>

    <div class="custom-container">
        <div class="custom-container-main">
            <div class="custom-login-wrap">
                <div class="custom-login-brand">
                    <span style="font-size: 32px; font-weight: 700; color: #1e3a5f; letter-spacing: -0.5px;">TDPUS</span>
                </div>

                <div class="custom-panel-body">
                    <h1>Add a Second Layer of Security</h1>

                    <p><b>Why Multi-factor Authentication is Important</b></p>
                    <p>Multi-factor authentication protects you against hackers. Even if someone steals your password,
                        your account and financial data stay secure.</p>

                    <p><b>How it Protects You</b></p>
                    <p>Multi-factor authentication adds a second layer of security to your account. Once you're set up,
                        you'll need both your password and an authenticator app to log in. The app confirms it's you
                        logging in, not someone else trying to access your data.</p>

                    <div class="twofa-buttons">
                        <a href="{{ route('authenticate') }}" class="btn btn-primary">Set Up Multi-factor
                            Authentication</a>
                        <a onclick="handleNotNow()" class="btn btn-primary">Not Now</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
        $('form').on('submit', function(e) {
            $('input[type="submit"]').attr('disabled', 'disabled');
        });
        function handleNotNow() {
            $.ajax({
                url: "{{ url('2fa-not-now') }}",
                type: "GET",
                dataType: 'json',
                success: function(result) {
                    window.location = '/'
                }
            });
    }
    </script>

</body>

</html>
