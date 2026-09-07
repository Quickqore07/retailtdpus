<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use Illuminate\Database\Eloquent\Model;
use App\Services\TemplateParser;
use App\Template\Template;
use App\User;

class WelcomeEmailAdmin extends Mailable
{
    use Queueable, SerializesModels;



    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $data;
    public function __construct($data)
    {

        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('New User Registration Notification')->view('emails.welcomeemail-admin')
            ->with(
                [
                    'name' => $this->data['name'],
                    'username' => $this->data['username'],
                    'email' => $this->data['email'],
                    'date' => Carbon::now()->format('m/d/Y h:i A'),
                ]
            );

    }
}
