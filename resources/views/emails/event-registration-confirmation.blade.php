@component('mail::message')
# Registration Confirmed

Dear {{ $userName }},

Your registration for **{{ $event->name }}** has been confirmed!

## Event Details:
- **Date**: {{ $event->start_time->format('F j, Y') }}
- **Time**: {{ $event->start_time->format('g:i A') }} - {{ $event->end_time->format('g:i A') }}
- **Timezone**: {{ $event->timezone }}

@if($event->category)
- **Category**: {{ $event->category->name }}
@endif

@if($event->description)
## Event Description:
{{ $event->description }}
@endif

## Important Information
Please make sure to arrive on time. If you need to cancel your registration, you can do so through the application.

@component('mail::button', ['url' => config('app.url')])
View Event Details
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
