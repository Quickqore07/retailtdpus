<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;


class OTPMailNEW extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;

    public $otp;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->subject = $data['subject'];
        $this->otp = $data['otp'];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->subject)->view('emails.otpemail');
    }
}
