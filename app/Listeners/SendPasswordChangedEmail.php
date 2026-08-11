<?php

namespace App\Listeners;

use App\Events\PasswordResetCompleted;
use App\Mail\PasswordChangedMail;
use App\Services\MailService;

class SendPasswordChangedEmail
{
    public function __construct(private MailService $mailService)
    {
    }

    public function handle(PasswordResetCompleted $event): void
    {
        $this->mailService->send($event->customer->email, new PasswordChangedMail($event->customer, $event->changedAt));
    }
}
