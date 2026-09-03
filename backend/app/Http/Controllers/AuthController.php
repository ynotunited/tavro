<?php

namespace App\Http\Controllers;

use App\Mail\InviteStaff;
use App\Mail\VerifyEmail;
use App\Models\Branch;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ApiResponse;

    /**
     * Login with email + password, return Sanctum token + user context.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($credentials)) {
            Log::channel('auth')->warning('Failed login attempt', [
                'email' => $credentials['email'],
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $this->error('Invalid credentials.', 401);
        }

        $user = Auth::user();

        if ($user->status !== 'active') {
            Auth::logout();

            Log::channel('auth')->warning('Login blocked — account deactivated', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            return $this->error('Your account has been deactivated. Please contact support.', 403);
        }

        if (! $user->hasVerifiedEmail()) {
            Auth::logout();

            Log::channel('auth')->warning('Login blocked — email not verified', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);

            return $this->error('Please verify your email address before signing in.', 403);
        }

        $session = $this->issueSession($user);

        Log::channel('auth')->info('Successful login', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        return $this->success([
            'user' => $user->load('roles'),
            'token' => $session['token'],
            'signing_secret' => $session['signing_secret'], // Show once — stored encrypted, never retrievable again
        ], 'Login successful');
    }

    /**
     * Return the authenticated user's profile.
     */
    public function me(Request $request)
    {
        return $this->success(['user' => $request->user()->load('roles', 'organization', 'branches')]);
    }

    /**
     * Revoke the current token (logout).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        Log::channel('auth')->info('User logged out', [
            'user_id' => $request->user()->id,
            'ip' => $request->ip(),
        ]);

        return $this->success(null, 'Logged out successfully');
    }

    /**
     * List all active sessions for the authenticated user.
     */
    public function sessions(Request $request)
    {
        $tokens = $request->user()->tokens()->select('id', 'name', 'last_used_at', 'created_at')->get();

        return $this->success($tokens);
    }

    /**
     * Revoke a specific session by token ID.
     */
    public function revokeSession(Request $request, int $tokenId)
    {
        $request->user()->tokens()->where('id', $tokenId)->delete();

        Log::channel('auth')->info('Session revoked', [
            'user_id' => $request->user()->id,
            'token_id' => $tokenId,
            'ip' => $request->ip(),
        ]);

        return $this->success(null, 'Session revoked');
    }

    /**
     * Send a password reset link to the given email.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if ($user && $user->status !== 'active') {
            Log::channel('auth')->warning('Password reset requested for deactivated account', [
                'email' => $request->email,
                'ip' => $request->ip(),
            ]);

            return $this->success(null, 'If an account with that email exists, a reset link has been sent.');
        }

        $status = Password::sendResetLink($request->only('email'));

        Log::channel('auth')->info('Password reset requested', [
            'email' => $request->email,
            'status' => $status,
            'ip' => $request->ip(),
        ]);

        return $this->success(null, 'If an account with that email exists, a reset link has been sent.');
    }

    /**
     * Reset the user's password using the token from the email link.
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => [
                'required',
                'min:8',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/',
            ],
            'password_confirmation' => 'required',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        Log::channel('auth')->info('Password reset completed', [
            'email' => $request->email,
            'status' => $status,
            'ip' => $request->ip(),
        ]);

        if ($status === Password::PASSWORD_RESET) {
            return $this->success(null, 'Password reset successfully. Please log in.');
        }

        return $this->error('Invalid or expired reset token.', 422);
    }

    /**
     * Send email verification notification.
     */
    public function sendVerificationEmail(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->error('Email already verified.', 422);
        }

        $this->sendVerificationMail($user);

        Log::channel('auth')->info('Verification email sent', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return $this->success(null, 'Verification link sent.');
    }

    /**
     * Resend a verification email for a not-yet-signed-in account.
     */
    public function resendVerification(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', strtolower(trim($request->email)))->first();

        if ($user && $user->status === 'active' && ! $user->hasVerifiedEmail()) {
            $this->sendVerificationMail($user);

            Log::channel('auth')->info('Verification email re-sent', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
            ]);
        }

        return $this->success(null, 'If an account with that email exists, a verification link has been sent.');
    }

    /**
     * Send the signed, expiring verification email that points at the frontend.
     */
    private function sendVerificationMail(User $user): void
    {
        $hash = sha1($user->getEmailForVerification());

        $signed = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->getKey(), 'hash' => $hash],
        );

        $query = (string) parse_url($signed, PHP_URL_QUERY);
        $base = rtrim((string) config('services.frontend.base_url'), '/');
        $url = $base.'/verify-email?url='.urlencode($signed);

        Mail::to($user)->send(new VerifyEmail($user, $url));
    }

    /**
     * Mark the user's email as verified.
     */
    public function verifyEmail(Request $request, string $id, string $hash)
    {
        $user = User::findOrFail($id);

        if (! hash_equals((string) $user->getKey(), (string) $id)) {
            return $this->error('Invalid verification link.', 403);
        }

        if (! hash_equals(sha1($user->getEmailForVerification()), (string) $hash)) {
            return $this->error('Invalid verification link.', 403);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->error('Email already verified.', 422);
        }

        $user->markEmailAsVerified();

        Log::channel('auth')->info('Email verified', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return $this->success(null, 'Email verified successfully.');
    }

    /**
     * Self-service signup. Creates the restaurant (organization), a Main
     * Branch, a 14-day Pro trial, and a verified owner account in one step.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/'],
            'business_name' => ['required', 'string', 'max:255'],
            'business_type' => ['nullable', 'string', 'max:255'],
        ]);

        $email = strtolower(trim($validated['email']));

        $org = DB::transaction(function () use ($validated, $email) {
            $org = Organization::create([
                'name' => trim($validated['business_name']),
                'type' => $validated['business_type'] ?? null,
                'currency' => 'NGN',
                'timezone' => 'Africa/Lagos',
                'tax_percentage' => 7.5,
                'service_charge_percentage' => 5,
            ]);

            // 14-day free trial on the 'pro' plan
            $plan = Plan::where('slug', 'pro')->first();
            if ($plan) {
                Subscription::create([
                    'organization_id' => $org->id,
                    'plan_id' => $plan->id,
                    'status' => 'trialing',
                    'trial_ends_at' => now()->addDays(14),
                    'current_period_start' => now(),
                    'current_period_end' => now()->addDays(14),
                ]);
            }

            $branch = Branch::create([
                'organization_id' => $org->id,
                'name' => 'Main Branch',
                'timezone' => 'Africa/Lagos',
            ]);

            $user = User::create([
                'organization_id' => $org->id,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'name' => $validated['first_name'].' '.$validated['last_name'],
                'email' => $email,
                'password' => $validated['password'],
                'status' => 'active',
            ]);
            $user->assignRole('owner');
            $user->branches()->attach($branch->id);

            return $org;
        });

        $user = User::where('email', $email)->first();

        // Auto-populate the new restaurant with the Nigerian food menu so the
        // owner can toggle what they sell and set their own prices instead of
        // adding everything from scratch.
        try {
            app(\App\Services\CatalogImportService::class)->importType($org->id, 'food');
        } catch (\Throwable $e) {
            Log::channel('auth')->warning('Failed to auto-import Nigerian menu', [
                'user_id' => $user->id,
                'org_id'  => $org->id,
                'error'   => $e->getMessage(),
            ]);
        }

        $this->sendVerificationMail($user);

        Log::channel('auth')->info('User registered', [
            'user_id' => $user->id,
            'email' => $user->email,
            'org_id' => $org->id,
            'org_name' => $org->name,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $this->success([
            'user' => $user->load('roles'),
        ], 'Your restaurant is ready. Confirm your email address to sign in — a verification link has been sent to '.$email.'.', 201);
    }

    /**
     * Accept a staff invitation: set a password, verify the email, and sign in.
     */
    public function acceptInvite(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).+$/'],
            'password_confirmation' => ['required'],
        ]);

        $user = User::where('email', strtolower(trim($validated['email'])))->first();

        if (! $user || ! $user->matchesInviteToken($validated['token'])) {
            return $this->error('This invitation link is invalid or has expired. Please ask your manager to send a new invite.', 422);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->error('This invitation has already been accepted. Please sign in instead.', 422);
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
            'invite_token' => null,
            'invite_expires_at' => null,
        ])->save();

        if (! empty($validated['first_name']) || ! empty($validated['last_name'])) {
            $first = $validated['first_name'] ?: $user->first_name;
            $last = $validated['last_name'] ?: $user->last_name;
            $user->forceFill([
                'first_name' => $first,
                'last_name' => $last,
                'name' => trim($first.' '.$last),
            ])->save();
        }

        $session = $this->issueSession($user);

        Log::channel('auth')->info('Invitation accepted', [
            'user_id' => $user->id,
            'email' => $user->email,
            'org_id' => $user->organization_id,
            'ip' => $request->ip(),
        ]);

        return $this->success([
            'user' => $user->load('roles'),
            'token' => $session['token'],
            'signing_secret' => $session['signing_secret'],
        ], 'Welcome! Your account is ready.');
    }

    /**
     * Resend a staff invitation for an email with a pending, unaccepted invite.
     */
    public function resendInvite(Request $request)
    {
        $validated = $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', strtolower(trim($validated['email'])))->first();

        // Do not leak whether an account/invite exists.
        if (! $user || $user->hasVerifiedEmail() || $user->status !== 'active') {
            return $this->success(null, 'If an account with that email has a pending invitation, a new link has been sent.');
        }

        $token = $user->issueInviteToken();
        Mail::to($user)->send(new InviteStaff($user, $this->inviteUrl($user, $token)));

        Log::channel('auth')->info('Invitation re-sent', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        return $this->success(null, 'If an account with that email has a pending invitation, a new link has been sent.');
    }

    /**
     * Create a Sanctum token plus an HMAC signing secret bound to it.
     */
    private function issueSession(User $user): array
    {
        $token = $user->createToken('auth_token');

        $signingSecret = bin2hex(random_bytes(32));
        $token->accessToken->forceFill([
            'signing_secret' => Crypt::encryptString($signingSecret),
        ])->save();

        return [
            'token' => $token->plainTextToken,
            'signing_secret' => $signingSecret,
        ];
    }

    /**
     * Frontend URL for accepting an invite (carries the raw token + email).
     */
    private function inviteUrl(User $user, string $token): string
    {
        return rtrim((string) config('services.frontend.base_url'), '/')
            .'/invite?token='.$token
            .'&email='.urlencode($user->email);
    }
}
