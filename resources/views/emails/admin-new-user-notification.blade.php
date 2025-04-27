@component('mail::message')
# New User Registration Alert

A new user has registered on the platform.

## User Details:
- **Name**: {{ $user->name }}
- **Email**: {{ $user->email }}
- **Role**: {{ $role }}
- **Registration Time**: {{ $registrationTime }}

@component('mail::button', ['url' => config('app.url')])
View User Details
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
