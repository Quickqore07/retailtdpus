<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use Illuminate\Database\Eloquent\Model;
use App\Services\TemplateParser;
use App\Template\Template;
use App\User;

class WelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $name;
    public function __construct($data)
    {
       
        $this->subject = $data['subject'];       
        $this->name = $data['name'];
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->subject)->view('emails.welcomeemail');

    }
}
