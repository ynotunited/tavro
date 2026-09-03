<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DuplicateUserController extends Controller
{
    use ApiResponse;

    /**
     * Find duplicate users without GROUP BY — uses window functions.
     *
     * Finds users sharing the same email (case-insensitive) or phone,
     * using ROW_NUMBER() to identify duplicates in a single scan.
     */
    public function index(Request $request)
    {
        // Only owners and general managers can view this
        if (!$request->user()->hasAnyRole(['owner', 'general_manager'])) {
            return $this->error('Forbidden.', 403);
        }

        $orgId = $request->user()->organization_id;

        // Find duplicate emails using window function (no GROUP BY needed)
        $duplicateEmails = DB::select("
            WITH ranked AS (
                SELECT
                    id,
                    email,
                    first_name,
                    last_name,
                    status,
                    created_at,
                    ROW_NUMBER() OVER (
                        PARTITION BY LOWER(email)
                        ORDER BY created_at ASC
                    ) AS rn,
                    COUNT(*) OVER (
                        PARTITION BY LOWER(email)
                    ) AS cnt
                FROM users
                WHERE organization_id = ?
            )
            SELECT id, email, first_name, last_name, status, created_at, rn, cnt
            FROM ranked
            WHERE cnt > 1
            ORDER BY LOWER(email), rn
        ", [$orgId]);

        // Find duplicate phones using window function
        $duplicatePhones = DB::select("
            WITH ranked AS (
                SELECT
                    id,
                    phone,
                    email,
                    first_name,
                    last_name,
                    ROW_NUMBER() OVER (
                        PARTITION BY phone
                        ORDER BY created_at ASC
                    ) AS rn,
                    COUNT(*) OVER (
                        PARTITION BY phone
                    ) AS cnt
                FROM users
                WHERE organization_id = ?
                  AND phone IS NOT NULL
                  AND phone != ''
            )
            SELECT id, phone, email, first_name, last_name, rn, cnt
            FROM ranked
            WHERE cnt > 1
            ORDER BY phone, rn
        ", [$orgId]);

        // Group into clusters
        $emailGroups = [];
        foreach ($duplicateEmails as $row) {
            $key = strtolower($row->email);
            $emailGroups[$key][] = [
                'id'         => $row->id,
                'email'      => $row->email,
                'first_name' => $row->first_name,
                'last_name'  => $row->last_name,
                'status'     => $row->status,
                'created_at' => $row->created_at,
                'is_primary' => $row->rn == 1,
            ];
        }

        $phoneGroups = [];
        foreach ($duplicatePhones as $row) {
            $phoneGroups[$row->phone][] = [
                'id'         => $row->id,
                'phone'      => $row->phone,
                'email'      => $row->email,
                'first_name' => $row->first_name,
                'last_name'  => $row->last_name,
                'is_primary' => $row->rn == 1,
            ];
        }

        return $this->success([
            'duplicate_emails' => $emailGroups,
            'duplicate_phones' => $phoneGroups,
            'summary'          => [
                'email_duplicates' => count($emailGroups),
                'phone_duplicates' => count($phoneGroups),
                'total_duplicates' => count($emailGroups) + count($phoneGroups),
            ],
        ]);
    }

    /**
     * Merge duplicate users — deactivate the duplicate, reassign data to the primary.
     */
    public function merge(Request $request)
    {
        if (!$request->user()->hasRole('owner')) {
            return $this->error('Only owners can merge duplicate accounts.', 403);
        }

        $validated = $request->validate([
            'primary_user_id'  => 'required|exists:users,id',
            'duplicate_user_id' => 'required|exists:users,id|different:primary_user_id',
        ]);

        $orgId = $request->user()->organization_id;
        $primary = User::where('id', $validated['primary_user_id'])
            ->where('organization_id', $orgId)
            ->first();
        $duplicate = User::where('id', $validated['duplicate_user_id'])
            ->where('organization_id', $orgId)
            ->first();

        if (!$primary || !$duplicate) {
            return $this->error('Users not found in your organization.', 404);
        }

        DB::transaction(function () use ($primary, $duplicate) {
            // Reassign orders
            \App\Models\Order::where('opened_by', $duplicate->id)
                ->update(['opened_by' => $primary->id]);
            \App\Models\Order::where('waiter_id', $duplicate->id)
                ->update(['waiter_id' => $primary->id]);

            // Reassign payments
            \App\Models\Payment::where('processed_by', $duplicate->id)
                ->update(['processed_by' => $primary->id]);

            // Reassign shifts
            \App\Models\Shift::where('user_id', $duplicate->id)
                ->update(['user_id' => $primary->id]);

            // Reassign audit logs
            \App\Models\AuditLog::where('actor_id', $duplicate->id)
                ->update(['actor_id' => $primary->id]);

            // Reassign branch memberships
            $duplicateBranches = $duplicate->branches()->pluck('branches.id');
            foreach ($duplicateBranches as $branchId) {
                if (!$primary->branches()->where('branches.id', $branchId)->exists()) {
                    $primary->branches()->attach($branchId);
                }
            }

            // Revoke all tokens and deactivate
            $duplicate->tokens()->delete();
            $duplicate->update(['status' => 'merged', 'email' => 'merged_' . $duplicate->id . '_' . $duplicate->email]);
        });

        return $this->success(null, 'Users merged successfully. The duplicate account has been deactivated.');
    }
}
