Hi {{ $user->first_name ?: $user->email }},

{{ $user->organization?->name ?? 'Your restaurant' }} has invited you to join their team on Tavro.

Accept your invitation and set your password here:

{{ $acceptUrl }}

This link expires in 48 hours. If you weren't expecting this invitation, you can safely ignore this email.

— The Tavro Team