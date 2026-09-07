<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;

class MailService
{
    public static function sendMail($toEmail, $subject, $htmlContent, $data = [], $cc = null, $attachments = [])
    {
        // Render your Blade template to HTML
        // Mail::html($htmlContent, function ($message) use ($toEmail, $subject) {
        //     $message->to($toEmail)
        //             ->subject($subject)
        //             ->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'));
        // });

        // return [
        //     'status' => true,
        //     'message' => 'Mail sent successfully'
        // ];
        $payload = [
            'sender' => [
                'name' => env('BREVO_MAIL_FROM_NAME'),
                'email' => env('BREVO_MAIL_FROM_ADDRESS'),
            ],
            'to' => [
                ['email' => $toEmail],
            ],
            'subject' => $subject,
            'htmlContent' => $htmlContent,
            'cc' => $cc,
        ];

        if (!empty($attachments)) {
            $payload['attachment'] = [];

            foreach ($attachments as $attachment) {
                $path = $attachment['path'] ?? null;

                if (empty($path) || !is_file($path) || !is_readable($path)) {
                    continue;
                }

                $payload['attachment'][] = [
                    'name' => $attachment['name'] ?? basename($path),
                    'content' => base64_encode(file_get_contents($path)),
                ];
            }

            if (empty($payload['attachment'])) {
                unset($payload['attachment']);
            }
        }

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.brevo.com/v3/smtp/email',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'accept: application/json',
                'api-key: ' . env('BREVO_API_KEY'),
                'content-type: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);

        if ($error) {
            return "cURL Error #: " . $error;
        }

        return json_decode($response, true);
    }
}
