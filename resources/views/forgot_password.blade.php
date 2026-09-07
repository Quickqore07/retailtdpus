<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TDPUS - Forgot Password</title>
    <meta name="title" content="Forgot Password | Reset Your Account Access">
    <meta name="description" content="Reset your password securely. Enter your email to receive password reset instructions.">
    <meta name="author" content="Quick Qore Inc">
    <meta name="robots" content="noindex, nofollow">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-white dark:bg-gray-900 overflow-hidden">
    <div class="relative flex flex-col w-full h-screen lg:flex-row">
        <!-- Left Side - Form -->
        <div class="flex flex-col flex-1 w-full px-4 py-6 overflow-y-auto sm:px-6 lg:w-1/2 lg:px-8">

            <div class="flex flex-col justify-center flex-1 w-full max-w-md mx-auto py-6 sm:py-8 lg:py-0">
                <div>
                    <!-- Header -->
                    <div class="mb-5 sm:mb-6 lg:mb-8">
                        <h1 class="mb-2 text-xl font-semibold text-gray-800 dark:text-white/90 sm:text-2xl lg:text-3xl">
                            Forgot Password
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Enter your email address and we'll send you a link to reset your password.
                        </p>
                    </div>

                    <!-- Alerts -->
                    <div id="alertSuccess" class="!hidden alert alert-success mb-4">
                        <x-svg-icon name="check" size="sm" />
                        <span id="successMessage">qweqwe</span>
                    </div>
                    <div id="alertError" class="!hidden alert alert-danger mb-4">
                        <x-svg-icon name="x" size="sm" />
                        <span id="errorMessage"></span>
                    </div>

                    <div>
                        <!-- Reset Password Form -->
                        <form id="forgotPasswordForm">
                            @csrf
                            <div class="flex flex-col gap-4 sm:gap-5">
                                <!-- Email -->
                                <div class="form-group">
                                    <label for="email" class="form-label form-label-required">Email</label>
                                    <input type="email" id="Email" name="email" value="{{ old('email') }}"
                                        placeholder="Enter your email address"
                                        class="form-input @if($errors->has('email')) form-input-error @endif" />
                                    @if ($errors->has('email'))
                                        <span class="form-error">{{ $errors->first('email') }}</span>
                                    @endif
                                </div>

                                <!-- Submit Button -->
                                <div>
                                    <button type="submit" id="emailSubmit" class="btn btn-primary btn-block">
                                        <span id="btnText">Send Reset Link</span>
                                        <span id="btnLoading" class="!hidden">
                                            <svg class="animate-spin h-5 w-5 inline-block mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Sending...
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Back to Login Link -->
                        <div class="mt-4 sm:mt-5 pb-4 sm:pb-0">
                            <p class="text-sm font-normal text-center text-gray-700 dark:text-gray-400 sm:text-start">
                                Remember your password?
                                <a href="/" class="link">Sign In</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side - Branding -->
        <div
            class="relative items-center justify-center hidden w-full lg:w-1/2 bg-gradient-to-br from-blue-950 via-blue-900 to-blue-800 dark:from-gray-800 dark:via-gray-900 dark:to-black lg:flex lg:sticky lg:top-0 lg:h-screen">
            <!-- Grid Pattern Background -->
            <div class="absolute inset-0 overflow-hidden opacity-10">
                <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                            <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="1" />
                        </pattern>
                    </defs>
                    <rect width="100%" height="100%" fill="url(#grid)" />
                </svg>
            </div>

            <div class="relative z-10 flex flex-col items-center max-w-sm px-6 text-center xl:px-8">
                <span class="block mb-6 text-3xl xl:text-4xl font-bold text-white tracking-tight">TDPUS</span>
                <h2 class="mb-3 text-xl font-bold text-white xl:text-2xl">Reset Your Password</h2>
                <p class="text-sm text-gray-300 dark:text-gray-400 xl:text-base">
                    Don't worry, it happens to the best of us. We will help you get back into your account securely.
                </p>
            </div>

            <!-- Decorative Circles -->
            <div class="absolute w-64 h-64 rounded-full -bottom-32 -left-32 bg-blue-500/20 blur-3xl"></div>
            <div class="absolute w-64 h-64 rounded-full -top-32 -right-32 bg-blue-400/20 blur-3xl"></div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
        function showAlert(type, message) {
            const successAlert = $('#alertSuccess');
            const errorAlert = $('#alertError');
            
            // Hide both alerts first
            successAlert.addClass('!hidden');
            errorAlert.addClass('!hidden');
            
            if (type === 'success') {
                $('#successMessage').text(message);
                successAlert.removeClass('!hidden');
            } else {
                $('#errorMessage').text(message);
                errorAlert.removeClass('!hidden');
            }
        }

        function setLoading(isLoading) {
            const btn = $('#emailSubmit');
            const btnText = $('#btnText');
            const btnLoading = $('#btnLoading');
            
            if (isLoading) {
                btn.prop('disabled', true).addClass('opacity-75');
                btnText.addClass('!hidden');
                btnLoading.removeClass('!hidden');
            } else {
                btn.prop('disabled', false).removeClass('opacity-75');
                btnText.removeClass('!hidden');
                btnLoading.addClass('!hidden');
            }
        }

        function handleSendOtp(e) {
            e.preventDefault();
            
            const email = $('#Email').val();
            
            if (!email) {
                showAlert('error', 'Please enter your email address');
                return;
            }
            
            setLoading(true);
            
            $.ajax({
                url: "{{ url('forgot') }}",
                type: "POST",
                data: {
                    email: email,
                    _token: '{{ csrf_token() }}'
                },
                dataType: 'json',
                success: function(result) {
                    if (result.success) {
                        showAlert('success', 'Password reset email sent! Please check your inbox and follow the instructions to reset your password.');
                        setTimeout(function() {
                            window.location.href = "/";
                        }, 3000);
                    } else {
                        showAlert('error', "Email doesn't exist in our records");
                    }
                    setLoading(false);
                },
                error: function(result) {
                    showAlert('error', result.responseJSON.message);
                    setLoading(false);
                }
            });
        }

        $(document).ready(function() {
            $('#forgotPasswordForm').on('submit', handleSendOtp);
        });
    </script>
</body>

</html>
