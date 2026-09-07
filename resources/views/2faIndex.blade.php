<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TDPUS - 2FA</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body,
        html {
            height: 100%;
            margin: 0;
            padding: 0;
            background-image: url('/images/otp.webp');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center center;
            background-attachment: fixed;
        }
    </style>
</head>

<body>
    <div class="main-container">
        <div class="container-main">
            <div class="login__wrap">
                <div class="login__content">
                    <div class="panel">
                        <div class="panel-heading">
                            <div class="login__brand">
                                <img src="{{ asset('/images/flow.png') }}" class="login__logo">
                            </div>
                        </div>
                        <div class="panel-body" id="usernamePassword">
                            <!-- <form class="login__form"> -->
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">  
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="text" id="Email" name="email" value="{{ $email }}"
                                    class="form-input" disabled>
                                @if ($errors->has('email'))
                                    <small class="error-control">{{ $errors->first('email') }}</small>
                                @endif
                            </div>
                            {{-- <small>Please enter a email which you have registered erlier </small> --}}
                            {{-- <div class="form-group">
                                <label class="form-label">{{ __('lang.password') }}</label>
                                <input type="password" id="Password" name="password" class="form-input">
                                @if ($errors->has('password'))
                                <small class="error-control">{{ $errors->first('password') }}</small>
                                @endif
                            </div> --}}


                            <div class="form-group" style="margin-top:10px">
                                <input onclick="handleSendOtp()" type="submit" value="{{ __('lang.next') }}"
                                    class="btn btn-primary" id='emailSubmit'>
                                <input onclick="handleLogout()" style="width: 110px;" value="Back to login"
                                    class="btn btn-primary">
                            </div>
                            <!-- </form> -->
                        </div>
                        <div class="panel-body" id="otpPassword" style="display: none">
                            <form class="login__form" action="/login" method="POST">
                                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <div class="form-group">
                                    <label class="form-label">{{ __('lang.otp') }}</label>
                                    <input type="number" id="OTP" name="otp" value="{{ old('otp') }}"
                                        class="form-input">
                                    @if ($errors->has('otp'))
                                        <small class="error-control">{{ $errors->first('otp') }}</small>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <!--<label class="form-label">{{ __('lang.password') }}</label>
                                <input type="password" id="Password" name="password" class="form-input">
                                @if ($errors->has('password'))
<small class="error-control">{{ $errors->first('password') }}</small>
@endif-->
                                    <div class="form-group" style="margin-top:10px">
                                        <input onclick="handleCredentials()" id="otpSubmit" type="submit"
                                            value="{{ __('lang.next') }}" class="btn btn-primary">
                                    </div>
                            </form>
                        </div>
                    </div>
                    {{-- <div class="panel-body" id="workgroupCompany" style="display: none">
                            <form class="login__form" action="/login" method="POST">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                <input type="hidden" id="email" name="email">
                                <input type="hidden" id="otp" name="otp">
                                <input type="hidden" id="password" name="password">
                                <div class="form-group">
                                    <label class="form-label">{{ __('lang.workgroups') }}</label>
                                    <select type="workgroup" name="workgroup" class="form-input" id="workgroup">
                                        <option value="">Please select</option>
                                    </select>
                                    @if ($errors->has('workgroup'))
                                    <small class="error-control">{{ $errors->first('workgroup') }}</small>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label class="form-label">{{ __('lang.company') }}</label>

                                    <select id="company" name="company" class="form-input"></select>
                                    @if ($errors->has('company'))
                                    <small class="error-control">{{ $errors->first('company') }}</small>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <input type="submit" value="{{ __('lang.login') }}" class="btn btn-primary">
                                </div>
                            </form>
                        </div> --}}
                </div>
            </div>
        </div>
    </div>
    </div>
</body>


<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
    function handleSendOtp() {
        const email = $('#email').val();
        $('#emailSubmit').prop('disabled', true)
        $.ajax({
            url: "{{ url('otp') }}",
            type: "POST",
            data: {
                email: email,
                _token: '{{ csrf_token() }}'

            },
            dataType: 'json',
            success: function(result) {
                if (result.success) {
                    $('#otpPassword').show();
                    $('#emailPassword').hide();
                    window.localStorage.removeItem('email');
                    window.localStorage.setItem('email', $('#email').val());
                    $('#email').val($('#email').val())
                    $('#password').val($('#Password').val())
                } else {
                    alert("Email doesn't not exists");
                }
                $('#emailSubmit').prop('disabled', false)
            },
            error: function(result) {
                alert("Something went wrong , can't send OTP");
                $('#emailSubmit').prop('disabled', false)

            }
        });
    }

    function handleCredentials() {


        const otp = $('#OTP').val();
        const email = $('#email').val();
        const password = $('#Password').val();

        $('#otpSubmit').prop('disabled', true)
        // $('#workgroupCompany').show();
        $('#otpPassword').hide();
        $.ajaxPrefilter(function(options, originalOptions, jqXHR) {
            jqXHR.setRequestHeader('X-CSRF-Token', '{{ csrf_token() }}');
        });
        $.ajax({
            url: "{{ url('verifyOTP') }}",
            type: "POST",
            data: {
                email: email,
                otp: otp,
                password: password,
                _token: '{{ csrf_token() }}'
            },
            dataType: 'json',
            success: function(result) {
                if (result.success) {
                    // $('#workgroupCompany').show();
                    //  $('#otpPassword').show();
                    // window.localStorage.removeItem('email');
                    window.localStorage.setItem('email', $('#email').val());
                    window.location.href = '/';

                    //console.log(window.localStorage.getItem(email));
                    // getWorkgroups();
                } else {
                    $('#otpPassword').show();
                    alert('Invalid OTP');
                }
                $('#otpSubmit').prop('disabled', false)

            },
            error: function(result) {
                $('#otpPassword').show();
                alert('Invalid credentials');
                $('#otpSubmit').prop('disabled', false)

            }
        });
    }


    function handleLogout() {
        $.ajax({
            url: "{{ url('logout') }}",
            type: "GET",
            dataType: 'json',
            success: function(result) {
                window.location.href = '/'
            },
            error: function(result) {
                window.location.href = '/'
                // alert('Invalid credentials')
            }
        });
    }
    $(document).ready(function() {
        $('#workgroupCompany').hide();
        $('#workgroup').on('change', function() {
            var workgrp_id = this.value;
            const email = $('#email').val();
            $("#company").html('');
            $.ajax({
                url: "{{ url('company-select') }}",
                type: "POST",
                data: {
                    email: email,
                    workgrp_id: workgrp_id,
                    _token: '{{ csrf_token() }}'
                },
                dataType: 'json',
                success: function(result) {
                    $('#company').html(
                        '<option value="">-- Select Company --</option>');
                    $.each(result, function(key, value) {
                        $("#company").append(
                            '<option value="' + value
                            .id + '">' +
                            value.name + '</option>');
                    });
                }
            });
        });
    });
</script>

</html>
