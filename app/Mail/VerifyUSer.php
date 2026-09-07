<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class VerifyUSer extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $subject;
    public $token;
    public $email;
    public $id;
    public function __construct($data)
    {
        $this->subject = $data['subject'];
        $this->token = $data['token'];
        $this->email = $data['email'];
        $this->id = $data['id'];    


        
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        //return $this->view('view.name');
        return $this->subject($this->subject)->view('emails.verifyuser');

    }
}
