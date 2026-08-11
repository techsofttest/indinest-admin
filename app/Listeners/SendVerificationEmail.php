<?php

namespace App\Listeners;

use App\Events\CustomerRegistered;
use App\Mail\VerifyEmailMail;
use App\Services\MailService;
use Illuminate\Support\Facades\URL;

class SendVerificationEmail
{
    public function __construct(private MailService $mailService)
    {
    }

    public function handle(CustomerRegistered $event): void
    {
        $customer = $event->customer;
        $verificationUrl = URL::temporarySignedRoute(
            'customer.verification.verify',
            now()->addMinutes(60),
            ['id' => $customer->id, 'hash' => sha1($customer->getEmailForVerification())]
        );

        $this->mailService->send($customer->email, new VerifyEmailMail($customer, $verificationUrl));
    }
}
