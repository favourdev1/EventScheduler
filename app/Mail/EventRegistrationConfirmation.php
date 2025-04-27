<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventRegistrationConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public EventRegistration $registration,
        public Event $event
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Registration Confirmed: {$this->event->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.event-registration-confirmation',
            with: [
                'event' => $this->event,
                'registration' => $this->registration,
                'userName' => $this->registration->user->name
            ]
        );
    }
}
