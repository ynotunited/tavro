<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class IssueController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Issue::with(['reporter', 'resolver'])
            ->where('organization_id', $request->user()->organization_id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        $issues = $query->orderByDesc('created_at')->paginate(25);

        return $this->success($issues);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'category'    => 'required|in:bug,feature,security,billing,other',
            'severity'    => 'required|in:low,medium,high,critical',
        ]);

        $issue = Issue::create([
            'organization_id' => $request->user()->organization_id,
            'reported_by'     => $request->user()->id,
            'title'           => $validated['title'],
            'description'     => $validated['description'],
            'category'        => $validated['category'],
            'severity'        => $validated['severity'],
            'status'          => 'open',
            'metadata'        => [
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
                'path'       => $request->path(),
            ],
        ]);

        return $this->success($issue, 'Issue reported', 201);
    }

    public function show(Issue $issue)
    {
        if ($issue->organization_id !== request()->user()->organization_id) {
            return $this->error('Not found.', 404);
        }

        return $this->success($issue->load(['reporter', 'resolver']));
    }

    public function update(Request $request, Issue $issue)
    {
        if ($issue->organization_id !== $request->user()->organization_id) {
            return $this->error('Not found.', 404);
        }

        $validated = $request->validate([
            'status'     => 'sometimes|in:open,in_progress,resolved,closed',
            'severity'   => 'sometimes|in:low,medium,high,critical',
            'resolved_at' => 'sometimes|nullable|date',
        ]);

        if (isset($validated['status']) && $validated['status'] === 'resolved') {
            $validated['resolved_at'] = $validated['resolved_at'] ?? now();
            $validated['resolved_by'] = $request->user()->id;
        }

        $issue->update($validated);

        return $this->success($issue->fresh()->load(['reporter', 'resolver']));
    }
}
