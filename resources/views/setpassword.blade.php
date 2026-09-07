<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set Password - TDPUS</title>
    <meta name="robots" content="noindex, nofollow">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-white dark:bg-gray-900 overflow-hidden">
    <div class="relative flex flex-col w-full h-screen lg:flex-row">
        <!-- Left Side - Form -->
        <div class="flex flex-col flex-1 w-full px-4 py-6 overflow-y-auto sm:px-6 lg:w-1/2 lg:px-8">

            <div class="flex flex-col justify-center flex-1 w-full max-w-md mx-auto py-6 sm:py-8 lg:py-0">
                <!-- Card Container -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 p-6 sm:p-8">
                    <!-- Header -->
                    <div class="mb-5 sm:mb-6 lg:mb-8">
                        <h1 class="mb-2 text-xl font-semibold text-gray-800 dark:text-white/90 sm:text-2xl lg:text-3xl">
                            Set Your Password
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Hi <span class="font-medium text-gray-700 dark:text-gray-300">{{ $name ?? 'there' }}</span>, welcome to TDPUS!
                        </p>
                    </div>

                    <!-- Welcome Message -->
                    <div class="mb-5 sm:mb-6">
                        <p class="mb-3 text-sm text-gray-600 dark:text-gray-400">
                            Your account has been created successfully. Please set your password below to activate your account.
                        </p>
                        
                        <!-- Tip Box -->
                        <div class="flex gap-3 p-3 sm:p-4 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 rounded-r">
                            <div class="flex-shrink-0 mt-0.5">
                                <svg class="w-4 h-4 sm:w-5 sm:h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <p class="text-xs sm:text-sm text-gray-700 dark:text-gray-300">
                                <strong class="font-semibold">Tip:</strong> Use a secure password that includes letters, numbers, and special characters.
                            </p>
                        </div>
                    </div>

                    <div>
                        <!-- Hidden Fields -->
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        <input type="hidden" name="otp_email" id="otp_email" value="{{ $email }}">
                        <input type="hidden" name="token" id="token" value="{{ $token }}">

                        <!-- Form -->
                        <div class="flex flex-col gap-4 sm:gap-5">
                            <!-- Email (Read-only) -->
                            <div class="form-group">
                                <label for="email" class="form-label">Email</label>
                                <input type="text" id="email" value="{{ $email }}" readonly
                                    class="form-input bg-gray-100 dark:bg-gray-700/50 cursor-not-allowed text-gray-600 dark:text-gray-400" />
                            </div>

                            <!-- New Password -->
                            <div class="form-group">
                                <label for="new_password" class="form-label form-label-required">New Password</label>
                                <div class="relative">
                                    <input type="password" id="new_password" name="new_password"
                                        placeholder="Enter your new password"
                                        class="form-input form-input-icon @if($errors->has('new_password')) form-input-error @endif" />
                                    <button type="button" id="togglePassword"
                                        class="absolute z-30 -translate-y-1/2 cursor-pointer right-4 top-1/2 text-gray-500 dark:text-gray-400 border-none bg-transparent outline-none focus:outline-none focus:ring-0 focus:border-none active:outline-none active:ring-0 appearance-none"
                                        style="outline: none !important; box-shadow: none !important;">
                                        <span class="icon-eye-off inline-flex">
                                            <x-svg-icon name="eye-off" size="sm" />
                                        </span>
                                        <span class="icon-eye hidden">
                                            <x-svg-icon name="eye" size="sm" />
                                        </span>
                                    </button>
                                </div>
                                @if ($errors->has('new_password'))
                                    <span class="form-error">{{ $errors->first('new_password') }}</span>
                                @endif
                                <span class="form-error" id="error-new_password" style="display: none;"></span>
                            </div>

                            <!-- Confirm Password -->
                            <div class="form-group">
                                <label for="confirm_password" class="form-label form-label-required">Confirm Password</label>
                                <div class="relative">
                                    <input type="password" id="confirm_password" name="confirm_password"
                                        placeholder="Confirm your new password"
                                        class="form-input form-input-icon @if($errors->has('confirm_password')) form-input-error @endif" />
                                    <button type="button" id="toggleConfirmPassword"
                                        class="absolute z-30 -translate-y-1/2 cursor-pointer right-4 top-1/2 text-gray-500 dark:text-gray-400 border-none bg-transparent outline-none focus:outline-none focus:ring-0 focus:border-none active:outline-none active:ring-0 appearance-none"
                                        style="outline: none !important; box-shadow: none !important;">
                                        <span class="icon-eye-off-confirm inline-flex">
                                            <x-svg-icon name="eye-off" size="sm" />
                                        </span>
                                        <span class="icon-eye-confirm hidden">
                                            <x-svg-icon name="eye" size="sm" />
                                        </span>
                                    </button>
                                </div>
                                @if ($errors->has('confirm_password'))
                                    <span class="form-error">{{ $errors->first('confirm_password') }}</span>
                                @endif
                                <span class="form-error" id="error-confirm_password" style="display: none;"></span>
                            </div>

                            <!-- Submit Button -->
                            <div>
                                <button type="button" onclick="handleSendOtp()" id="emailSubmit" class="btn btn-primary btn-block">
                                    Set Password
                                </button>
                            </div>
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
                <h2 class="mb-3 text-xl font-bold !text-white xl:text-2xl">Welcome to TDPUS</h2>
                <p class="text-sm text-gray-300 dark:text-gray-400 xl:text-base">
                    TDPUS Workforce & Operations Platform
                    Onboarding, time tracking, payroll, and benefits — all in one place.
                </p>
            </div>

            <!-- Decorative Circles -->
            <div class="absolute w-64 h-64 rounded-full -bottom-32 -left-32 bg-blue-500/20 blur-3xl"></div>
            <div class="absolute w-64 h-64 rounded-full -top-32 -right-32 bg-blue-400/20 blur-3xl"></div>
        </div>
    </div>


    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
        // Password visibility toggle for new password
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('new_password');
            const iconEyeOff = this.querySelector('.icon-eye-off');
            const iconEye = this.querySelector('.icon-eye');

            const isPasswordVisible = passwordInput.type === 'password';
            passwordInput.type = isPasswordVisible ? 'text' : 'password';

            iconEyeOff.classList.toggle('hidden', isPasswordVisible);
            iconEyeOff.classList.toggle('inline-flex', !isPasswordVisible);
            iconEye.classList.toggle('hidden', !isPasswordVisible);
            iconEye.classList.toggle('inline-flex', isPasswordVisible);
        });

        // Password visibility toggle for confirm password
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('confirm_password');
            const iconEyeOff = this.querySelector('.icon-eye-off-confirm');
            const iconEye = this.querySelector('.icon-eye-confirm');

            const isPasswordVisible = passwordInput.type === 'password';
            passwordInput.type = isPasswordVisible ? 'text' : 'password';

            iconEyeOff.classList.toggle('hidden', isPasswordVisible);
            iconEyeOff.classList.toggle('inline-flex', !isPasswordVisible);
            iconEye.classList.toggle('hidden', !isPasswordVisible);
            iconEye.classList.toggle('inline-flex', isPasswordVisible);
        });

        // Check token validity on page load
        $(document).ready(function() {
            const token = "{{ request()->query('token') }}";
            if (token) {
                $.ajax({
                    url: "/check-token/setup_user/" + token,
                    type: "GET",
                    success: function(response) {
                        if (!response.success) {
                            alert('Invalid or expired link');
                            window.location.href = "/";
                        }
                    }
                });
            }
        });

        function handleSendOtp() {
            // Clear previous errors
            $('.form-error').hide().text('');
            
            const new_password = $('#new_password').val();
            const confirm_password = $('#confirm_password').val();
            const token = $('#token').val();
            const email = $('#otp_email').val();

            // Validation
            if (!new_password) {
                $('#error-new_password').text('Please enter a password.').show();
                return;
            }

            if (!confirm_password) {
                $('#error-confirm_password').text('Please confirm your password.').show();
                return;
            }

            if (new_password !== confirm_password) {
                $('#error-confirm_password').text('Passwords do not match.').show();
                return;
            }

            // Disable submit button
            $('#emailSubmit').prop('disabled', true).addClass('opacity-75');

            $.ajax({
                url: "{{ url('setnewpass') }}",
                type: "POST",
                data: {
                    new_password: new_password,
                    confirm_password: confirm_password,
                    email: email,
                    token: token,
                    _token: '{{ csrf_token() }}'
                },
                dataType: 'json',
                success: function(result) {
                    if (result.success) {
                        alert('Your password has been set successfully. You can now sign in.');
                        window.location.href = "/";
                    } else {
                        alert("Email doesn't exist");
                        $('#emailSubmit').prop('disabled', false).removeClass('opacity-75');
                    }
                },
                error: function(result) {
                    $('#emailSubmit').prop('disabled', false).removeClass('opacity-75');
                    
                    // Handle validation errors
                    if (result.responseJSON && result.responseJSON.errors) {
                        // Map errors to specific fields
                        $.each(result.responseJSON.errors, function(field, messages) {
                            const errorElement = $('#error-' + field);
                            if (errorElement.length) {
                                errorElement.text(messages[0]).show();
                            }
                        });
                    } else if (result.responseJSON && result.responseJSON.message) {
                        // Show general error message
                        alert(result.responseJSON.message);
                    } else {
                        alert('An error occurred. Please try again.');
                    }
                }
            });
        }
    </script>
</body>

</html>
