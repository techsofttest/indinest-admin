<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Mail\Mailable;

class ForgotPasswordMail extends Mailable
{
    public function __construct(public Customer $customer, public string $resetUrl)
    {
    }

    public function build()
    {
        return $this->subject('Reset your password - ' . config('app.name'))
            ->view('emails.password_reset', [
                'user' => $this->customer,
                'resetUrl' => $this->resetUrl,
            ]);
    }
}
