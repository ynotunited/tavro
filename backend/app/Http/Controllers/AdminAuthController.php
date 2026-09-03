<?php

namespace App\Http\Controllers;

use App\Models\AdminUser;
use App\Services\AdminAuditLogger;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    use ApiResponse;

    /**
     * Admin login. Rate-limited by ThrottleAdminLogin (IP + email keys) and
     * audited on failure/success.
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $admin = AdminUser::where('email', strtolower(trim($validated['email'])))->first();

        if (! $admin || ! Hash::check($validated['password'], $admin->password)) {
            return $this->error('Invalid credentials.', 401);
        }

        if (! $admin->is_active) {
            AdminAuditLogger::log('admin.login.deactivated', $admin->id, request: $request, status: 403);

            return $this->error('This admin account is deactivated.', 403);
        }

        Auth::guard('admin')->login($admin, (bool) $request->boolean('remember'));

        $admin->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $request->session()->regenerate();

        // Regenerate the session ID keyed to the admin guard.
        $request->session()->put('admin_authenticated', true);

        AdminAuditLogger::log('admin.login.success', $admin->id, request: $request, status: 200);

        return $this->success([
            'admin' => $admin->only(['id', 'name', 'email', 'last_login_at']),
        ], 'Login successful.');
    }

    public function logout(Request $request)
    {
        $admin = $request->user('admin');

        AdminAuditLogger::log('admin.logout', $admin?->id, request: $request, status: 200);

        Auth::guard('admin')->logout();
        $request->session()->forget('admin_authenticated');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->success(null, 'Logged out.');
    }

    public function me(Request $request)
    {
        return $this->success($request->user('admin')?->only(['id', 'name', 'email', 'last_login_at']));
    }
}
