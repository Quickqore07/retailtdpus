<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $token;

    public function __construct($user, $token)
    {
        $this->user = $user;
        $this->token = $token;
    }

    public function build()
    {
        $token = !empty($this->token) ? urlencode($this->token) : null;
        $email = !empty($this->user->email) ? urlencode($this->user->email) : null;

        $resetUrl = url('/setpassword/?token=' . $token . '&email=' . $email);

        return $this->subject('Set Your Password')
                    ->view('emails.setpassword')
                    ->with([
                        'name' => $this->user->name,
                        'resetUrl' => $resetUrl
                    ]);
    }
}


?>