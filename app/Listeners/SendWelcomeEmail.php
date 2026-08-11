<?php

namespace App\Listeners;

use App\Events\CustomerVerified;
use App\Mail\WelcomeMail;
use App\Services\MailService;

class SendWelcomeEmail
{
    public function __construct(private MailService $mailService)
    {
    }

    public function handle(CustomerVerified $event): void
    {
        $this->mailService->send($event->customer->email, new WelcomeMail($event->customer));
    }
}
