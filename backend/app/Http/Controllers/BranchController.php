<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    use ApiResponse;

    /**
     * List all branches for the authenticated user's organization.
     */
    public function index(Request $request)
    {
        $branches = Branch::where('organization_id', $request->user()->organization_id)->get();
        return $this->success($branches);
    }

    /**
     * Show a specific branch.
     */
    public function show(Request $request, Branch $branch)
    {
        if ($branch->organization_id !== $request->user()->organization_id) {
            return $this->error('Forbidden', 403);
        }

        return $this->success($branch);
    }

    /**
     * Create a new branch.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'address'         => 'nullable|string|max:255',
            'phone'           => 'nullable|string|max:50',
            'timezone'        => 'nullable|string|max:50|in:Africa/Lagos,Africa/Abidjan,Africa/Nairobi,Africa/Accra,Africa/Johannesburg,Europe/London,Europe/Paris,US/Eastern,US/Pacific,Asia/Dubai',
            'operating_hours' => 'nullable|array|max:7',
            'operating_hours.*' => 'array',
            'operating_hours.*.open' => 'nullable|string|max:5',
            'operating_hours.*.close' => 'nullable|string|max:5',
        ]);

        $branch = Branch::create(array_merge($validated, [
            'organization_id' => $request->user()->organization_id,
        ]));

        return $this->success($branch, 'Branch created successfully', 201);
    }

    /**
     * Update a branch's details.
     */
    public function update(Request $request, Branch $branch)
    {
        if ($branch->organization_id !== $request->user()->organization_id) {
            return $this->error('Forbidden', 403);
        }

        $validated = $request->validate([
            'name'            => 'sometimes|string|max:255',
            'address'         => 'sometimes|nullable|string|max:255',
            'phone'           => 'sometimes|nullable|string|max:50',
            'timezone'        => 'sometimes|string|max:50|in:Africa/Lagos,Africa/Abidjan,Africa/Nairobi,Africa/Accra,Africa/Johannesburg,Europe/London,Europe/Paris,US/Eastern,US/Pacific,Asia/Dubai',
            'operating_hours' => 'sometimes|array|max:7',
            'operating_hours.*' => 'array',
            'operating_hours.*.open' => 'nullable|string|max:5',
            'operating_hours.*.close' => 'nullable|string|max:5',
        ]);

        $branch->update($validated);

        return $this->success($branch, 'Branch updated successfully');
    }
}
