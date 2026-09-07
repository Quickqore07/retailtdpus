<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TDPUS - Sign In</title>
    <meta name="title" content="Login to Your Account | Secure Access to Your Bookkeeping Software">
    <meta name="description"
        content="Log in to access your bookkeeping software. Manage your finances, track expenses, and view your invoices securely.">
    <meta name="keywords"
        content="login bookkeeping software, accounting software login, secure access, bookkeeping login, business accounting access">
    <meta name="author" content="Quick Qore Inc">
    <meta name="robots" content="noindex, nofollow">
    <meta name="google-signin-client_id"
        content="548400103136-qsti7760d0a8vo0nq99irqdml2ssdq8v.apps.googleusercontent.com">
    @vite(['resources/css/app.css', 'resources/css/style.css'])
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
                            Sign In
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Enter your email and password to sign in!
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
                        <!-- Social Login Buttons -->
                        <!-- <div class="w-full">
                            <button type="button" id="googleLoginBtn" class="btn btn-social btn-block">
                                <x-svg-icon name="google" size="md" />
                                <span>Sign in with Google</span>
                            </button>
                        </div>

                        <div class="divider">
                            <div class="divider-line"></div>
                            <div class="divider-text">
                                <span>Or</span>
                            </div>
                        </div> -->

                        <!-- Login Form -->
                        <form action="/login" method="POST" id="loginForm">
                            @csrf
                            <div class="flex flex-col gap-4 sm:gap-5">
                                <!-- Email -->
                                <div class="form-group">
                                    <label for="email" class="form-label form-label-required">Username</label>
                                    <input type="text" id="username" name="username" value="{{ old('username') }}"
                                        placeholder="Enter your email or username"
                                        class="form-input @if($errors->has('username')) form-input-error @endif" />
                                    @if ($errors->has('username'))
                                        <span class="form-error">{{ $errors->first('username') }}</span>
                                    @endif
                                </div>

                                <!-- Password -->
                                <div class="form-group">
                                    <label for="password" class="form-label form-label-required">Password</label>
                                    <div class="relative">
                                        <input type="password" id="password" name="password"
                                            placeholder="Enter your password"
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

                                <!-- <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <label for="keepLoggedIn" class="form-checkbox">
                                        <input type="checkbox" id="keepLoggedIn" name="remember" class="form-checkbox-input" />
                                        <div class="form-checkbox-box">
                                            <x-svg-icon name="checkmark" size="xs" class="form-checkbox-icon checkmark-icon" />
                                        </div>
                                        <span class="form-checkbox-label">Keep me logged in</span>
                                    </label>
                                    <a href="/forgotpassword" class="link whitespace-nowrap">Forgot password?</a>
                                </div> -->

                                <!-- Submit Button -->
                                <div>
                                    <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                                </div>
                            </div>
                        </form>

                        <!-- Sign Up Link -->
                        <!-- <div class="mt-4 sm:mt-5 pb-4 sm:pb-0">
                            <p class="text-sm font-normal text-center text-gray-700 dark:text-gray-400 sm:text-start">
                                Don't have an account?
                                <a href="{{ route('register') }}" class="link">Sign Up</a>
                            </p>
                        </div> -->
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
    <script src="https://apis.google.com/js/platform.js?onload=renderButton" async defer></script>
    <script>
        // Checkbox toggle (only if element exists)
        window.localStorage.clear();
        const keepLoggedInCheckbox = document.getElementById('keepLoggedIn');
        if (keepLoggedInCheckbox) {
            keepLoggedInCheckbox.addEventListener('change', function() {
                const checkmarkIcon = this.nextElementSibling.querySelector('.checkmark-icon');
                checkmarkIcon.style.display = this.checked ? 'block' : 'none';
            });
        }

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

        // Form submission
        $('#loginForm').on('submit', function(e) {
            $(this).find('button[type="submit"]').attr('disabled', 'disabled').addClass('opacity-75');
        });

        // Google login
        function handleGoogleLogin() {
            var currentUrl = window.location.href;
            if (currentUrl.includes('#')) {
                const fragment = window.location.hash.substring(1);
                const params = new URLSearchParams(fragment);
                const accessToken = params.get('access_token');
                $.ajax({
                    url: 'https://www.googleapis.com/userinfo/v2/me',
                    type: "GET",
                    headers: {
                        Authorization: `Bearer ${accessToken}`
                    },
                    dataType: 'json',
                    success: function(result) {
                        login(result);
                    }
                });
            }
        }

        function login(data) {
            $.ajax({
                url: "{{ url('continue-google') }}",
                type: "POST",
                data: {
                    email: data.email,
                    first_name: data.given_name,
                    last_name: data.family_name ? data.family_name : data.name,
                    name: data.given_name,
                    _token: '{{ csrf_token() }}'
                },
                dataType: 'json',
                success: function(result) {
                    if (result.mfa == "Active") {
                        window.location = '/authenticate';
                    } else if (result.mfa == "Pending") {
                        window.location = '/twofactor';
                    } else {
                        window.location = '/';
                    }
                }
            });
        }



        $(document).ready(function() {
            handleGoogleLogin();
            window.localStorage.clear();
        });
    </script>
</body>

</html>
