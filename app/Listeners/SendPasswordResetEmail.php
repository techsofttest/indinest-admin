<?php

namespace App\Listeners;

use App\Events\PasswordResetRequested;
use App\Mail\ForgotPasswordMail;
use App\Services\MailService;

class SendPasswordResetEmail
{
    public function __construct(private MailService $mailService)
    {
    }

    public function handle(PasswordResetRequested $event): void
    {
        $this->mailService->send($event->customer->email, new ForgotPasswordMail($event->customer, $event->resetUrl));
    }
}
