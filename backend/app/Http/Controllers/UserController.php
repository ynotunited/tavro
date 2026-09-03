<?php

namespace App\Http\Controllers;

use App\Mail\InviteStaff;
use App\Models\Branch;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserController extends Controller
{
    use ApiResponse;

    /**
     * List all users in the authenticated user's organization.
     */
    public function index(Request $request)
    {
        $users = User::with('roles')
            ->where('organization_id', $request->user()->organization_id)
            ->get();

        return $this->success($users);
    }

    /**
     * Invite a new user to the organization.
     *
     * The invitee is created unverified with no password. A token-scoped
     * invitation email lets them set a password and verify their address
     * (`POST /api/v1/auth/invite/accept`).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => 'required|string|in:general_manager,branch_manager,cashier,waiter,bartender,kitchen_staff,inventory_manager',
            'branch_ids' => 'nullable|array',
            'branch_ids.*' => 'exists:branches,id',
        ]);

        // `users.password` is DB NOT NULL, so the invitee gets a throwaway hash.
        // It is never revealed — they set their own password via the invite link.
        $temporaryPassword = Str::random(12).'!'.Str::random(4);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => $validated['first_name'].' '.$validated['last_name'],
            'email' => strtolower(trim($validated['email'])),
            'password' => Hash::make($temporaryPassword),
            'organization_id' => $request->user()->organization_id,
            'status' => 'active',
        ]);

        $user->assignRole($validated['role']);

        if (! empty($validated['branch_ids'])) {
            // Verify all branch IDs belong to the user's organization
            $validBranchIds = Branch::where('organization_id', $request->user()->organization_id)
                ->whereIn('id', $validated['branch_ids'])
                ->pluck('id')
                ->toArray();

            if (count($validBranchIds) !== count($validated['branch_ids'])) {
                $user->delete();

                return $this->error('One or more branch IDs do not belong to your organization.', 422);
            }

            $user->branches()->sync($validBranchIds);
        }

        // Issue the invite and deliver the acceptance link by email.
        $token = $user->issueInviteToken();
        $url = rtrim((string) config('services.frontend.base_url'), '/')
            .'/invite?token='.$token
            .'&email='.urlencode($user->email);

        Mail::to($user)->send(new InviteStaff($user, $url));

        Log::channel('auth')->info('Staff invited', [
            'user_id' => $user->id,
            'email' => $user->email,
            'org_id' => $request->user()->organization_id,
            'invited_by' => $request->user()->id,
            'ip' => $request->ip(),
        ]);

        return $this->success($user->load('roles'), 'User invited successfully. An invitation email has been sent.', 201);
    }

    /**
     * Update a user's details.
     */
    public function update(Request $request, User $user)
    {
        if ($user->organization_id !== $request->user()->organization_id) {
            return $this->error('Forbidden', 403);
        }

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:50',
            'branch_ids' => 'sometimes|array',
            'branch_ids.*' => 'exists:branches,id',
        ]);

        $user->update(array_filter([
            'first_name' => $validated['first_name'] ?? null,
            'last_name' => $validated['last_name'] ?? null,
            'phone' => $validated['phone'] ?? null,
        ]));

        if (isset($validated['branch_ids'])) {
            // Verify all branch IDs belong to the user's organization
            $validBranchIds = Branch::where('organization_id', $request->user()->organization_id)
                ->whereIn('id', $validated['branch_ids'])
                ->pluck('id')
                ->toArray();

            $user->branches()->sync($validBranchIds);
        }

        return $this->success($user->load('roles'), 'User updated successfully');
    }

    /**
     * Assign a role to a user. Owner-only.
     */
    public function assignRole(Request $request, User $user)
    {
        if (! $request->user()->hasRole('owner')) {
            return $this->error('Only owners can assign roles.', 403);
        }

        if ($user->organization_id !== $request->user()->organization_id) {
            return $this->error('Forbidden', 403);
        }

        $validated = $request->validate([
            'role' => 'required|string|in:owner,general_manager,branch_manager,cashier,waiter,bartender,kitchen_staff,inventory_manager',
        ]);

        $user->syncRoles([$validated['role']]);

        return $this->success($user->load('roles'), 'Role assigned successfully');
    }

    /**
     * Deactivate a user and revoke all their tokens.
     */
    public function destroy(Request $request, User $user)
    {
        if ($user->organization_id !== $request->user()->organization_id) {
            return $this->error('Forbidden', 403);
        }

        if ($user->id === $request->user()->id) {
            return $this->error('You cannot deactivate your own account.', 403);
        }

        $user->update(['status' => 'inactive']);
        $user->tokens()->delete();

        return $this->success(null, 'User deactivated and all sessions revoked.');
    }
}
