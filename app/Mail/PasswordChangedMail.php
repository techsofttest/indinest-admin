<?php

namespace App\Mail;

use App\Models\Customer;
use Illuminate\Mail\Mailable;

class PasswordChangedMail extends Mailable
{
    public function __construct(public Customer $customer, public ?string $changedAt = null)
    {
    }

    public function build()
    {
        return $this->subject('Your password was changed - ' . config('app.name'))
            ->view('emails.password_changed', [
                'customer' => $this->customer,
                'changedAt' => $this->changedAt,
            ]);
    }
}
