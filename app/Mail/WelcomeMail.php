<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Mail\Mailable;

class WelcomeMail extends Mailable
{
    public function __construct(public Customer $customer)
    {
    }

    public function build()
    {
        return $this->subject('Welcome to ' . config('app.name'))
            ->view('emails.welcome', ['customer' => $this->customer]);
    }
}
