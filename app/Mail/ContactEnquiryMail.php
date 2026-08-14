<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactEnquiryMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Contact Enquiry: ' . ($this->data['type'] ?? 'General Inquiry'),
            replyTo: $this->data['email'] ?? null,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact_enquiry',
        );
    }
}
