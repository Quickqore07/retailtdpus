<?php

namespace App\Http\Controllers;

use App\Mail\WelcomeEmailAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Role;
use App\Mail\WelcomeEmail;
use App\Mail\VerifyUSer;
use Illuminate\Support\Facades\Auth;
use App\Services\MailService;

class RegistrationController extends Controller
{
    protected $mailService;

    public function __construct( MailService $mailService)
    {
        $this->mailService = $mailService;
    }
    public function create(Request $request)
    {
        return view('signup');
    }

    public function store(Request $request)
    {

        $this->validate(
            $request,
            [
                'email' => 'required|max:255|email|unique:users,email',
                'password' => 'required|min:6|regex:/^(?=.*[0-9])(?=.*[A-Z])(?=.*[@$!%*#?&])[A-Za-z\d@$!%*#?&]+$/',
                'first_name' => 'required',
                'last_name' => 'required',
                'phone_number' => 'required|numeric|unique:users,phone',
                'terms' => 'required',
                'username' => 'required|unique:users,email'
            ],
            [
                'password.regex' => 'The password must contain at least one digit, one uppercase letter, and one special character.',
                'terms.required' => 'Please check the terms and conditions option.',
            ]
        );

        // $role=Role::create([
        //     'name' => 'Admin',
        //     'description' => 'Complete Access to flow',
        //     'permissions' => json_encode(Permission::schema())
        // ]);
        $user = new User;
        $input = $request->all();
        $user->email = trim($input['email']);
        $user->username = $input['username'];
        $user->name = $input['first_name'] . " " . $input['last_name'];
        $user->phone = $input['phone_number'];
        $user->password = bcrypt($input['password']);
        $user->role_id = Role::first()->id;
        $user->save();

        $last_inserted_id = $user->id;

        $request->session()->put('username', $input['username']);
        $request->session()->put('email_verified', false);
        $subject = "Verify Your Email";
        $token = Str::random(64);
        $data = [
            'subject' => $subject,
            'token' => $token,
            'email' => $user->otp_email,
            'id' => $last_inserted_id
        ];

        $this->mailService->sendMail(
            $user->otp_email,
            $subject,
            (new VerifyUser($data))->render(),
        );


        try {
            $data = [
                'subject' => "Welcome to QuickOB Workforce & Operations Platform",
                'name' => $user->name,
                'username' => $user->email,
                'email' => $user->otp_email,
            ];

            // Send welcome email via Mandrill
            $this->mailService->sendMail(
                $user->otp_email,
                "Welcome to QuickOB Workforce & Operations Platform",
                (new WelcomeEmail($data))->render(),
                $data
            );

            // Send admin notification via Mandrill
            $this->mailService->sendMail(
                env('ADMIN_MAIL'),
                "New User Registration - QuickOB",
                (new WelcomeEmailAdmin($data))->render(),
                $data
            );

            // Also send to secondary admin
            if (env('ADMIN_SECONDARY_MAIL')) {
                $this->mailService->sendMail(
                    env('ADMIN_SECONDARY_MAIL'),
                    "New User Registration - QuickOB",
                    (new WelcomeEmailAdmin($data))->render(),
                    $data
                );
            }
            //code...
        } catch (\Throwable $th) {
            // Fallback to Laravel Mail
        }

        return redirect('/verify-email');
        
        // return to_json([
        //     'saved' => true
        // ]);

    }
    public function verifyAccount(Request $request, $token, $email, $id)
    {
        $verifyUser = User::where('email', $email)->first();
        $message = 'Sorry your email cannot be identified.';

        $request->session()->put('username', $verifyUser->username);
        // dd($request->session());
        $request->session()->put('email_verified', true);
        if (!is_null($verifyUser)) {
            if ($verifyUser->registration_flag == '0' || $verifyUser->registration_flag == '2') {
                $verifyUser->token = $token;
                $verifyUser->registration_flag = 2;
                $verifyUser->save();

                return redirect('/')->with('success', "Your e-mail is verified. You can now proceed with next step.");
            } else {
                return redirect('/')->with('success', "Your e-mail is already verified. You can login.");
            }
        }
        //dd($message);

        return to_json([
            'saved' => true,
            $message
        ]);
    }

    public function googleLogin(Request $request)
    {

        $user = User::where('otp_email', $request->email)->first();
        // dd( $user);
        if ($user && $user->login_attempts < 5) {
            $user->save();
            Auth::login($user, true);
            if (!$user->registration_flag) {
                return $user;
            }

            // $user = User::where('email', $request->email)->first();    // Get User Data

            $request->session()->put('username', $user->username);

            // $request->session()->put('industry', $industry);
            $user->login_attempts = 0; // Reset login attempts
            $user->save();
            // $user = User::where('email', $request->email)->first();    // Get User Data

            return $user;
        } else {

            $user = new User;
            $input = $request->all();
            $user->email = trim($input['email']);
            $user->username = trim($input['email']);
            $user->name = $input['first_name'] . " " . $input['last_name'];
            // $user->otp_phone = $input['phone_number'];
            // $user->password = bcrypt( $input['password']);
            $user->role_id = Role::first()->id;
            // $user->verified_email=1;
            $user->save();
            Auth::login($user, true);


            return $user;
        }
    }

    public function verifyEmail(Request $request)
    {
        return view('auth.verify-email');
    }
}
