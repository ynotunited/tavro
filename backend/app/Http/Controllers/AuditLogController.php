<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'user_id'    => 'nullable|integer|exists:users,id',
            'action'     => 'nullable|string|max:100',
            'entity_type' => 'nullable|string|max:100',
            'date_from'  => 'nullable|date_format:Y-m-d',
            'date_to'    => 'nullable|date_format:Y-m-d',
            'page'       => 'nullable|integer|min:1',
        ]);

        $query = AuditLog::with('actor:id,first_name,last_name')
            ->where('organization_id', $request->user()->organization_id);

        if (!empty($validated['user_id'])) {
            $query->where('actor_id', $validated['user_id']);
        }

        if (!empty($validated['action'])) {
            $escaped = addcslashes($validated['action'], '%_');
            $query->where('action', 'like', '%' . $escaped . '%');
        }

        if (!empty($validated['entity_type'])) {
            $query->where('entity_type', $validated['entity_type']);
        }

        if (!empty($validated['date_from'])) {
            $query->whereDate('created_at', '>=', $validated['date_from']);
        }

        if (!empty($validated['date_to'])) {
            $query->whereDate('created_at', '<=', $validated['date_to']);
        }

        $logs = $query->orderByDesc('created_at')->paginate(50);

        return response()->json($logs);
    }
}
