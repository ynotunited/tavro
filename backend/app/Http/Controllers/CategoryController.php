<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $categories = Category::where('organization_id', $request->user()->organization_id)
            ->orderBy('sort_order')
            ->withCount('products')
            ->get();

        return $this->success($categories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'color'      => 'nullable|string|max:20',
            'icon'       => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer',
        ]);

        $category = Category::create(array_merge($validated, [
            'organization_id' => $request->user()->organization_id,
        ]));

        return $this->success($category, 'Category created', 201);
    }

    public function update(Request $request, Category $category)
    {
        $this->authorizeOrg($category, $request);

        $validated = $request->validate([
            'name'       => 'sometimes|string|max:255',
            'color'      => 'sometimes|nullable|string|max:20',
            'icon'       => 'sometimes|nullable|string|max:50',
            'sort_order' => 'sometimes|integer',
        ]);

        $category->update($validated);

        return $this->success($category, 'Category updated');
    }

    public function destroy(Request $request, Category $category)
    {
        $this->authorizeOrg($category, $request);
        $category->delete();

        return $this->success(null, 'Category deleted');
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'ids'   => 'required|array|min:1|max:50',
            'ids.*' => 'required|integer|exists:categories,id',
        ]);

        foreach ($validated['ids'] as $index => $id) {
            Category::where('id', $id)
                ->where('organization_id', $request->user()->organization_id)
                ->update(['sort_order' => $index]);
        }

        return $this->success(null, 'Categories reordered');
    }

    private function authorizeOrg(Category $category, Request $request)
    {
        if ($category->organization_id !== $request->user()->organization_id) {
            abort(403);
        }
    }
}
