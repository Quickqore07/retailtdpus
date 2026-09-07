<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TDPUS - Register</title>
    <meta name="title" content="Register for TDPUS Workforce & Operations Platform">
    <meta name="description"
        content="Register for TDPUS Workforce & Operations Platform. Onboarding, time tracking, payroll, and benefits — all in one place.">
    <meta name="keywords"
        content="register TDPUS, TDPUS signup, free trial, TDPUS registration, business accounting sign up">
    <meta name="author" content="Quick Qore Inc">
    <meta name="robots" content="index, follow">
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
                            Create Account
                        </h1>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Fill in the details below to get started!
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
                        <!-- Registration Form -->
                        <form action="/register" method="POST" id="registerForm">
                            @csrf
                            <div class="flex flex-col gap-4 sm:gap-5">
                                <!-- Name Row -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <!-- First Name -->
                                    <div class="form-group">
                                        <label for="first_name" class="form-label form-label-required">First Name</label>
                                        <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}"
                                            placeholder="Enter your first name"
                                            class="form-input @if($errors->has('first_name')) form-input-error @endif" />
                                        @if ($errors->has('first_name'))
                                            <span class="form-error">{{ $errors->first('first_name') }}</span>
                                        @endif
                                    </div>

                                    <!-- Last Name -->
                                    <div class="form-group">
                                        <label for="last_name" class="form-label form-label-required">Last Name</label>
                                        <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}"
                                            placeholder="Enter your last name"
                                            class="form-input @if($errors->has('last_name')) form-input-error @endif" />
                                        @if ($errors->has('last_name'))
                                            <span class="form-error">{{ $errors->first('last_name') }}</span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Phone Number -->
                                <div class="form-group">
                                    <label for="phone_number" class="form-label form-label-required">Phone Number</label>
                                    <input type="text" id="phone_number" name="phone_number" value="{{ old('phone_number') }}"
                                        placeholder="Enter your phone number"
                                        class="form-input @if($errors->has('phone_number')) form-input-error @endif" />
                                    @if ($errors->has('phone_number'))
                                        <span class="form-error">{{ $errors->first('phone_number') }}</span>
                                    @endif
                                </div>

                                <!-- Username -->
                                <div class="form-group">
                                    <label for="username" class="form-label form-label-required">Username</label>
                                    <input type="text" id="username" name="username" value="{{ old('username') }}"
                                        placeholder="Choose a username"
                                        class="form-input @if($errors->has('username')) form-input-error @endif" />
                                    @if ($errors->has('username'))
                                        <span class="form-error">{{ $errors->first('username') }}</span>
                                    @endif
                                </div>

                                <!-- Email -->
                                <div class="form-group">
                                    <label for="email" class="form-label form-label-required">Email</label>
                                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                                        placeholder="Enter your email address"
                                        class="form-input @if($errors->has('email')) form-input-error @endif" />
                                    @if ($errors->has('email'))
                                        <span class="form-error">{{ $errors->first('email') }}</span>
                                    @endif
                                </div>

                                <!-- Password -->
                                <div class="form-group">
                                    <label for="password" class="form-label form-label-required">Password</label>
                                    <div class="relative">
                                        <input type="password" id="password" name="password"
                                            placeholder="Create a password"
                                            class="form-input form-input-icon @if($errors->has('password')) form-input-error @endif" />
                                        <button type="button" id="togglePassword"
                                            class="absolute z-30 -translate-y-1/2 cursor-pointer right-4 top-1/2 text-gray-500 dark:text-gray-400 focus:outline-none">
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

                                <!-- Terms Checkbox -->
                                <div class="form-group">
                                    <label for="terms" class="form-checkbox">
                                        <input type="checkbox" id="terms" name="terms" value="1" class="form-checkbox-input" />
                                        <div class="form-checkbox-box">
                                            <x-svg-icon name="checkmark" size="xs" class="form-checkbox-icon checkmark-icon" />
                                        </div>
                                        <span class="form-checkbox-label text-sm">
                                            I confirm that I have read and agree to TDPUS
                                            <a href="https://quickqore.com/terms-conditions/" target="_blank" class="link">Terms of Service</a>
                                            and
                                            <a href="https://quickqore.com/privacy-policy/" target="_blank" class="link">Privacy Policy</a>.
                                        </span>
                                    </label>
                                    @if ($errors->has('terms'))
                                        <span class="form-error">{{ $errors->first('terms') }}</span>
                                    @endif
                                </div>

                                <!-- Submit Button -->
                                <div>
                                    <button type="submit" class="btn btn-primary btn-block">Register</button>
                                </div>
                            </div>
                        </form>

                        <!-- Sign In Link -->
                        <div class="mt-4 sm:mt-5 pb-4 sm:pb-0">
                            <p class="text-sm font-normal text-center text-gray-700 dark:text-gray-400 sm:text-start">
                                Already have an account?
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
                <h2 class="mb-3 text-xl font-bold text-white xl:text-2xl">Join TDPUS Today</h2>
                <p class="text-sm text-gray-300 dark:text-gray-400 xl:text-base">
                    Start managing your workforce with our powerful TDPUS workforce & operations platform. Onboarding, time tracking, payroll, and benefits — all in one place.
                </p>
            </div>

            <!-- Decorative Circles -->
            <div class="absolute w-64 h-64 rounded-full -bottom-32 -left-32 bg-blue-500/20 blur-3xl"></div>
            <div class="absolute w-64 h-64 rounded-full -top-32 -right-32 bg-blue-400/20 blur-3xl"></div>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script>
        // Checkbox toggle
        document.getElementById('terms').addEventListener('change', function() {
            const checkmarkIcon = this.nextElementSibling.querySelector('.checkmark-icon');
            checkmarkIcon.style.display = this.checked ? 'block' : 'none';
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

        // Form submission
        $('#registerForm').on('submit', function(e) {
            $(this).find('button[type="submit"]').attr('disabled', 'disabled').addClass('opacity-75');
        });
    </script>
</body>

</html>
