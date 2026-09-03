<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $suppliers = Supplier::where('branch_id', $request->user()->branch_id)->orderBy('name')->get();
        return response()->json(['data' => $suppliers]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'phone'        => 'nullable|string|max:50',
            'email'        => 'nullable|email|max:255',
            'address'      => 'nullable|string|max:500',
        ]);

        $supplier = Supplier::create([...$validated, 'branch_id' => $request->user()->branch_id]);
        return response()->json(['data' => $supplier], 201);
    }

    public function update(Request $request, Supplier $supplier)
    {
        if ($supplier->branch_id !== $request->user()->branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'name'         => 'sometimes|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'phone'        => 'nullable|string|max:50',
            'email'        => 'nullable|email|max:255',
            'address'      => 'nullable|string|max:500',
        ]);
        $supplier->update($validated);
        return response()->json(['data' => $supplier]);
    }

    public function destroy(Request $request, Supplier $supplier)
    {
        if ($supplier->branch_id !== $request->user()->branch_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $supplier->delete();
        return response()->json(['message' => 'Supplier deleted.']);
    }
}
