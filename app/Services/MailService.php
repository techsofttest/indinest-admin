<?php

namespace App\Services;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MailService
{
    public function adminRecipients(): array
    {
        $raw = (string) env('ADMIN_EMAIL', '');
        $recipients = array_values(array_filter(array_map('trim', explode(',', $raw))));

        $valid = array_values(array_filter($recipients, fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL)));

        if ($recipients && ! $valid) {
            Log::warning('Invalid admin email configuration.', ['admin_email' => $raw]);
        }

        return $valid;
    }

    public function send(string $to, Mailable $mailable): void
    {
        try {
            Mail::to($to)->send($mailable);
        } catch (\Throwable $e) {
            Log::error('Failed to send email.', ['to' => $to, 'error' => $e->getMessage()]);
        }
    }

    public function sendToMany(array $recipients, Mailable $mailable): void
    {
        foreach ($recipients as $recipient) {
            $this->send($recipient, $mailable);
        }
    }
}
