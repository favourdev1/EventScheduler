@component('mail::message')
# New Event Registration

Hello,

A new participant has registered for your event **{{ $event->name }}**.

## Participant Details:
- **Name**: {{ $participant->name }}
- **Email**: {{ $participant->email }}
- **Registration Time**: {{ $registration->created_at->format('F j, Y g:i A') }}

## Event Status:
- **Spots Remaining**: {{ $spotsLeft }}
- **Total Registered**: {{ $event->active_participants_count }}

@component('mail::button', ['url' => config('app.url')])
View Event Details
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
