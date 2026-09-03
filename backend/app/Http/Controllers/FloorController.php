<?php

namespace App\Http\Controllers;

use App\Models\Floor;
use Illuminate\Http\Request;

class FloorController extends Controller
{
    public function index(Request $request)
    {
        $floors = Floor::where('branch_id', $request->user()->branch_id)
            ->with('tables')
            ->get();
        return response()->json(['data' => $floors]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $floor = Floor::create([
            'name' => $validated['name'],
            'organization_id' => $request->user()->organization_id,
            'branch_id' => $request->user()->branch_id,
        ]);

        return response()->json(['data' => $floor]);
    }

    public function update(Request $request, Floor $floor)
    {
        if ($floor->branch_id !== $request->user()->branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
        ]);

        $floor->update($validated);
        return response()->json(['data' => $floor]);
    }

    public function destroy(Request $request, Floor $floor)
    {
        if ($floor->branch_id !== $request->user()->branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $floor->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
