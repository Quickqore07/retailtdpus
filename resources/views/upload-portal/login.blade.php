<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    @vite(['resources/css/app.css', 'resources/css/style.css'])

    <title>Upload Portal - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: #f9fafb;
            transition: background-color 0.3s ease;
        }

        .login-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
            transition: background-color 0.3s ease, box-shadow 0.3s ease;
        }

        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }

        .login-header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .login-header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .login-form {
            padding: 40px 30px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
            transition: color 0.3s ease;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
            color: #111827;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .error-message {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .login-footer {
            text-align: center;
            padding: 20px 30px 30px;
            color: #6b7280;
            font-size: 13px;
            transition: color 0.3s ease;
        }

        .password-toggle-wrapper {
            position: relative;
        }

        /* Dark Mode Styles */
        @media (prefers-color-scheme: dark) {
            body {
                background: #111827;
            }

            .login-container {
                background: #1f2937;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
            }

            .form-group label {
                color: #e5e7eb;
            }

            .form-group input {
                background: #374151;
                border-color: #4b5563;
                color: #f9fafb;
            }

            .form-group input:focus {
                border-color: #667eea;
                background: #374151;
                box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
            }

            .form-group input::placeholder {
                color: #9ca3af;
            }

            .error-message {
                background: #7f1d1d;
                border-color: #991b1b;
                color: #fca5a5;
            }

            .login-footer {
                color: #9ca3af;
            }
        }

    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1 class="!text-white">Upload Portal</h1>
            <p>Sign in to access document uploads</p>
        </div>

        <form action="{{ route('upload-portal.login.submit') }}" method="POST" class="login-form">
            @csrf

            @if ($errors->any())
                <div class="error-message">
                    @foreach ($errors->all() as $error)
                        {{ $error }}
                    @endforeach
                </div>
            @endif

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="{{ old('username') }}" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-toggle-wrapper relative">
                    <input type="password" id="password" name="password" required>
                    <button type="button" id="togglePassword"
                    class="absolute z-30 -translate-y-1/2 cursor-pointer right-4 top-1/2 text-gray-500 dark:text-gray-400 border-none bg-transparent outline-none focus:outline-none focus:ring-0 focus:border-none active:outline-none active:ring-0 appearance-none"
                        style="outline: none !important; box-shadow: none !important;">
                        <span class="icon-eye-off inline-flex" id="eyeOffIcon">
                            <x-svg-icon name="eye-off" size="sm" />
                        </span>
                        <span class="icon-eye hidden" id="eyeIcon">
                            <x-svg-icon name="eye" size="sm" />
                        </span>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-login">Sign In</button>
        </form>

        <div class="login-footer">
            <p>&copy; {{ date('Y') }} Upload Portal. All rights reserved.</p>
        </div>
    </div>
</body>

<script>
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

    
</script>
</html>
