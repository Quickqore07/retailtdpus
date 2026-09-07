<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\User;


class ForgotpassMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;

    public $email;
    public $route_url;
    public $token;
    /**
     * Create a new message instance.
     *
     * @return void
     */

    public $is_admin = false;
    public function __construct($data)
    {
        $this->subject = $data['subject'];
        $this->email = $data['email'];
        $this->token = $data['token'];
        if (isset($data['admin']) && $data['admin']) {
            $this->route_url = route('admin.updatepassword.verify', $this->token);
        } else if (isset($data['bulk_sales']) && $data['bulk_sales']) {
            $this->route_url = route('bulksales.verify', $this->email);
        } else {
            $this->route_url = route('updatepassword.verify', $this->token);
        }
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Use the $this->route_url to ensure it's available to the view.
        return $this->subject($this->subject)
            ->view('emails.forgotemail')
            ->with([
                'route_url' => $this->route_url,
            ]);
    }
}
