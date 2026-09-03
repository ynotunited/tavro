<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class OrganizationController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            // other onboarding fields
        ]);

        $org = Organization::create($validated);

        // Attach a 14-day free trial on the 'pro' plan by default
        $proPlan = \App\Models\Plan::where('slug', 'pro')->first();
        if ($proPlan) {
            \App\Models\Subscription::create([
                'organization_id' => $org->id,
                'plan_id' => $proPlan->id,
                'status' => 'trialing',
                'trial_ends_at' => now()->addDays(14),
                'current_period_start' => now(),
                'current_period_end' => now()->addDays(14),
            ]);
        }

        return $this->success($org, 'Organization created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, int $id)
    {
        // Tenant isolation: org records are only visible to their own users.
        if ($id !== $request->user()->organization_id) {
            return $this->error('Organization not found.', 404);
        }

        return $this->success(Organization::findOrFail($id));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Organization $organization)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        // Tenant isolation: org records are only editable by their own users.
        if ($id !== $request->user()->organization_id) {
            return $this->error('Organization not found.', 404);
        }

        $organization = Organization::findOrFail($id);

        $validated = $request->validate([
            'name'                      => 'sometimes|string|max:255',
            'type'                      => 'sometimes|string|max:255',
            'currency'                  => 'sometimes|string|max:10',
            'timezone'                  => 'sometimes|string|max:64',
            'tax_percentage'            => 'sometimes|numeric|min:0|max:100',
            'service_charge_percentage' => 'sometimes|numeric|min:0|max:100',
        ]);

        $organization->update($validated);

        return $this->success($organization, 'Organization updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Organization $organization)
    {
        //
    }
}
