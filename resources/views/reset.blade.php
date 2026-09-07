<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TDPUS - Reset Password</title>
    <meta name="title" content="Reset Your Password | Secure Password Recovery">
    <meta name="description"
        content="Reset your password securely. Enter your new password to regain access to your TDPUS account.">
    <meta name="keywords"
        content="password reset, account recovery, TDPUS, secure password change">
    <meta name="author" content="Quick Qore Inc">
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
                            Reset Password
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Enter your new password below to reset your account password.
                        </p>
                    </div>

                    <!-- Alerts -->
                    @if ($message = Session::get('success'))
                        <div class="alert alert-success">
                            <x-svg-icon name="check" size="sm" />
                            <span>{{ $message }}</span>
                        </div>
                    @endif
                    @if ($message = Session::get('error'))
                        <div class="alert alert-danger">
                            <x-svg-icon name="x" size="sm" />
                            <span>{{ $message }}</span>
                        </div>
                    @endif

                    <div>
                        <!-- Reset Password Form -->
                        <form action="{{ route('savepassword') }}" method="POST" id="resetPasswordForm">
                            @csrf
                            <input type="hidden" name="token" value="{{ request()->segment(2) }}">
                            <div class="flex flex-col gap-4 sm:gap-5">
                                <!-- Password -->
                                <div class="form-group">
                                    <label for="password" class="form-label form-label-required">Password</label>
                                    <div class="relative">
                                        <input type="password" id="password" name="password"
                                            placeholder="Enter your new password"
                                            class="form-input form-input-icon @if($errors->has('password')) form-input-error @endif" />
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
                                    @if ($errors->has('password'))
                                        <span class="form-error">{{ $errors->first('password') }}</span>
                                    @endif
                                </div>

                                <!-- Confirm Password -->
                                <div class="form-group">
                                    <label for="password_confirmation" class="form-label form-label-required">Confirm Password</label>
                                    <div class="relative">
                                        <input type="password" id="password_confirmation" name="password_confirmation"
                                            placeholder="Confirm your new password"
                                            class="form-input form-input-icon @if($errors->has('password_confirmation')) form-input-error @endif" />
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
                                    @if ($errors->has('password_confirmation'))
                                        <span class="form-error">{{ $errors->first('password_confirmation') }}</span>
                                    @endif
                                </div>

                                <!-- Submit Button -->
                                <div>
                                    <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
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
                <h2 class="mb-3 text-xl font-bold !text-white xl:text-2xl">Secure Password Reset</h2>
                <p class="text-sm text-gray-300 dark:text-gray-400 xl:text-base">
                    Create a strong new password to keep your account secure and protect your TDPUS data.
                </p>
            </div>

            <!-- Decorative Circles -->
            <div class="absolute w-64 h-64 rounded-full -bottom-32 -left-32 bg-blue-500/20 blur-3xl"></div>
            <div class="absolute w-64 h-64 rounded-full -top-32 -right-32 bg-blue-400/20 blur-3xl"></div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
        $(document).ready(function() {
            // Check token validity
            const token = "{{ request()->segment(2) }}";
            if (token) {
                $.ajax({
                    url: "/check-token/password_reset/" + token,
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

        // Password visibility toggle
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const iconEyeOff = this.querySelector('.icon-eye-off');
            const iconEye = this.querySelector('.icon-eye');

            const isPasswordVisible = passwordInput.type === 'password';
            passwordInput.type = isPasswordVisible ? 'text' : 'password';

            iconEyeOff.classList.toggle('hidden', isPasswordVisible);
            iconEyeOff.classList.toggle('inline-flex', !isPasswordVisible);
            iconEye.classList.toggle('hidden', !isPasswordVisible);
            iconEye.classList.toggle('inline-flex', isPasswordVisible);
        });

        // Confirm password visibility toggle
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password_confirmation');
            const iconEyeOff = this.querySelector('.icon-eye-off-confirm');
            const iconEye = this.querySelector('.icon-eye-confirm');

            const isPasswordVisible = passwordInput.type === 'password';
            passwordInput.type = isPasswordVisible ? 'text' : 'password';

            iconEyeOff.classList.toggle('hidden', isPasswordVisible);
            iconEyeOff.classList.toggle('inline-flex', !isPasswordVisible);
            iconEye.classList.toggle('hidden', !isPasswordVisible);
            iconEye.classList.toggle('inline-flex', isPasswordVisible);
        });

        // Form submission
        $('#resetPasswordForm').on('submit', function(e) {
            $(this).find('button[type="submit"]').attr('disabled', 'disabled').addClass('opacity-75');
        });
    </script>
</body>

</html>
