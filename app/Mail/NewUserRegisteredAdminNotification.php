<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewUserRegisteredAdminNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $newUser
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New User Registration: {$this->newUser->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.admin-new-user-notification',
            with: [
                'user' => $this->newUser,
                'registrationTime' => $this->newUser->created_at->format('F j, Y g:i A'),
                'role' => ucfirst($this->newUser->role)
            ]
        );
    }
}
