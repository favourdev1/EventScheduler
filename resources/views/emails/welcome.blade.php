@component('mail::message')
# Welcome to {{ config('app.name') }}!

Dear {{ $user->name }},

Thank you for registering with us! Your account has been successfully created as a **{{ $role }}**.

@if($user->role === 'organizer')
As an organizer, you can:
- Create and manage events
- Track participant registrations
- Update event details
- View participant lists
@else
As a user, you can:
- Browse available events
- Register for events
- Manage your registrations
- View your event schedule
@endif

@component('mail::button', ['url' => config('app.url')])
Get Started
@endcomponent

If you have any questions, feel free to contact our support team.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
