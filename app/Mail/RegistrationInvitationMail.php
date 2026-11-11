<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $invitationUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'RentTrack - Registration Approved'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.registration-invitation'
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
