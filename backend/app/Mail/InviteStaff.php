<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InviteStaff extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $acceptUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You\'ve been invited to join '
                . ($this->user->organization?->name ?? 'a restaurant')
                . ' on Tavro',
        );
    }

    public function content(): Content
    {
        return new Content(text: 'mails.invite-staff');
    }
}