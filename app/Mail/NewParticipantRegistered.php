<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewParticipantRegistered extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Event $event,
        public User $participant,
        public EventRegistration $registration
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New Registration for {$this->event->name}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.new-participant-registered',
            with: [
                'event' => $this->event,
                'participant' => $this->participant,
                'registration' => $this->registration,
                'spotsLeft' => $this->event->max_participants - $this->event->active_participants_count
            ]
        );
    }
}
