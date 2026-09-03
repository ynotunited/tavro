Hi {{ $user->first_name ?: $user->email }},

Welcome to Tavro! Confirm your email address to finish creating your account.

Confirm your email here:

{{ $verifyUrl }}

This link expires in 60 minutes. If you didn't create an account on Tavro, you can safely ignore this email.

— The Tavro Team