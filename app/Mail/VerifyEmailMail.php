<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Mail\Mailable;

class VerifyEmailMail extends Mailable
{
    public function __construct(public Customer $customer, public string $verificationUrl)
    {
    }

    public function build()
    {
        return $this->subject('Verify your email address - ' . config('app.name'))
            ->view('emails.verify_email', [
                'customer' => $this->customer,
                'verificationUrl' => $this->verificationUrl,
            ]);
    }
}
