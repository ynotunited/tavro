<?php

namespace App\Http\Controllers;

use App\Models\Table;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function index(Request $request)
    {
        $tables = Table::where('branch_id', $request->user()->branch_id)->get();
        return response()->json(['data' => $tables]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'floor_id' => 'required|exists:floors,id',
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1|max:999',
            'shape' => 'required|string|in:square,round,rectangle',
            'pos_x' => 'required|numeric|min:0|max:99999',
            'pos_y' => 'required|numeric|min:0|max:99999',
        ]);

        $table = Table::create(array_merge($validated, [
            'organization_id' => $request->user()->organization_id,
            'branch_id' => $request->user()->branch_id,
        ]));

        return response()->json(['data' => $table]);
    }

    public function update(Request $request, Table $table)
    {
        if ($table->branch_id !== $request->user()->branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'capacity' => 'sometimes|integer|min:1|max:999',
            'shape' => 'sometimes|string|in:square,round,rectangle',
            'pos_x' => 'sometimes|numeric|min:0|max:99999',
            'pos_y' => 'sometimes|numeric|min:0|max:99999',
        ]);

        $table->update($validated);
        
        event(new \App\Events\TableStatusUpdated($table));
        
        return response()->json(['data' => $table]);
    }
    
    public function updateStatus(Request $request, Table $table)
    {
        if ($table->branch_id !== $request->user()->branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:AVAILABLE,OCCUPIED,CLEANING,RESERVED',
        ]);

        $table->update(['status' => $validated['status']]);
        
        event(new \App\Events\TableStatusUpdated($table));
        
        return response()->json(['data' => $table]);
    }

    public function destroy(Request $request, Table $table)
    {
        if ($table->branch_id !== $request->user()->branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $table->delete();
        return response()->json(['message' => 'Deleted successfully']);
    }
}
