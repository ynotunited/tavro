<?php

namespace App\Http\Controllers;

use App\Models\ModifierGroup;
use App\Models\Modifier;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ModifierGroupController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $groups = ModifierGroup::where('organization_id', $request->user()->organization_id)
            ->with('modifiers')
            ->get();

        return $this->success($groups);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'min_selections' => 'nullable|integer|min:0',
            'max_selections' => 'nullable|integer|min:1',
            'is_required'    => 'boolean',
            'modifiers'      => 'nullable|array',
            'modifiers.*.name'  => 'required|string|max:255',
            'modifiers.*.price' => 'nullable|numeric|min:0',
        ]);

        $group = ModifierGroup::create(array_merge(
            collect($validated)->except('modifiers')->toArray(),
            ['organization_id' => $request->user()->organization_id]
        ));

        if (!empty($validated['modifiers'])) {
            foreach ($validated['modifiers'] as $i => $modifier) {
                $group->modifiers()->create(array_merge($modifier, ['sort_order' => $i]));
            }
        }

        return $this->success($group->load('modifiers'), 'Modifier group created', 201);
    }

    public function update(Request $request, ModifierGroup $modifierGroup)
    {
        $this->authorizeOrg($modifierGroup, $request);

        $validated = $request->validate([
            'name'           => 'sometimes|string|max:255',
            'min_selections' => 'sometimes|integer|min:0',
            'max_selections' => 'sometimes|integer|min:1',
            'is_required'    => 'sometimes|boolean',
            'modifiers'      => 'sometimes|array',
            'modifiers.*.id'    => 'nullable|exists:modifiers,id',
            'modifiers.*.name'  => 'required|string|max:255',
            'modifiers.*.price' => 'nullable|numeric|min:0',
        ]);

        $modifierGroup->update(collect($validated)->except('modifiers')->toArray());

        if (isset($validated['modifiers'])) {
            $modifierGroup->modifiers()->delete();
            foreach ($validated['modifiers'] as $i => $modifier) {
                $modifierGroup->modifiers()->create(array_merge($modifier, ['sort_order' => $i]));
            }
        }

        return $this->success($modifierGroup->load('modifiers'), 'Modifier group updated');
    }

    public function destroy(Request $request, ModifierGroup $modifierGroup)
    {
        $this->authorizeOrg($modifierGroup, $request);
        $modifierGroup->delete();

        return $this->success(null, 'Modifier group deleted');
    }

    private function authorizeOrg(ModifierGroup $group, Request $request)
    {
        if ($group->organization_id !== $request->user()->organization_id) {
            abort(403);
        }
    }
}
