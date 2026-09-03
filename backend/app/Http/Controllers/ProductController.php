<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $products = Product::where('organization_id', $request->user()->organization_id)
            ->with(['category', 'variants'])
            ->orderBy('sort_order')
            ->get();

        return $this->success($products);
    }

    public function show(Request $request, string $productId)
    {
        $product = Product::find($productId);

        if (! $product) {
            abort(404);
        }

        $this->authorizeOrg($product, $request);

        return $this->success(
            $product->load(['category', 'variants', 'modifierGroups.modifiers', 'recipe.items'])
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'category_id'        => 'nullable|exists:categories,id',
            'sku'                => 'nullable|string|max:100|unique:products,sku',
            'description'        => 'nullable|string|max:2000',
            'type'               => 'required|in:food,drink,cocktail,package,modifier,service',
            'selling_price'      => 'required|numeric|min:0|max:999999999',
            'cost_price'         => 'nullable|numeric|min:0|max:999999999',
            'is_taxable'         => 'boolean',
            'has_service_charge' => 'boolean',
            'is_available'       => 'boolean',
            'track_inventory'    => 'boolean',
            'image'              => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:5120',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('products', 'public');
        }
        unset($validated['image']);

        $product = Product::create(array_merge($validated, [
            'organization_id' => $request->user()->organization_id,
        ]));

        return $this->success($product->load('category'), 'Product created', 201);
    }

    public function update(Request $request, string $productId)
    {
        $product = Product::find($productId);

        if (! $product) {
            abort(404);
        }

        $this->authorizeOrg($product, $request);

        $validated = $request->validate([
            'name'               => 'sometimes|string|max:255',
            'category_id'        => 'sometimes|nullable|exists:categories,id',
            'sku'                => "sometimes|nullable|string|max:100|unique:products,sku,{$product->id}",
            'description'        => 'sometimes|nullable|string|max:2000',
            'type'               => 'sometimes|in:food,drink,cocktail,package,modifier,service',
            'selling_price'      => 'sometimes|numeric|min:0|max:999999999',
            'cost_price'         => 'sometimes|nullable|numeric|min:0|max:999999999',
            'is_taxable'         => 'sometimes|boolean',
            'has_service_charge' => 'sometimes|boolean',
            'is_available'       => 'sometimes|boolean',
            'track_inventory'    => 'sometimes|boolean',
            'sort_order'         => 'sometimes|integer',
            'image'              => 'nullable|image|mimes:jpeg,jpg,png,webp,gif|max:5120',
        ]);

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $validated['image_path'] = $request->file('image')->store('products', 'public');
        }
        unset($validated['image']);

        $product->update($validated);

        return $this->success($product->load(['category', 'variants']), 'Product updated');
    }

    public function toggleAvailability(Request $request, string $productId)
    {
        $product = Product::find($productId);

        if (! $product) {
            abort(404);
        }

        $this->authorizeOrg($product, $request);
        $product->update(['is_available' => !$product->is_available]);

        return $this->success(['is_available' => $product->is_available]);
    }

    public function destroy(Request $request, string $productId)
    {
        $product = Product::find($productId);

        if (! $product) {
            abort(404);
        }

        $this->authorizeOrg($product, $request);
        $product->delete();

        return $this->success(null, 'Product deleted');
    }

    private function authorizeOrg(Product $product, Request $request)
    {
        if ($product->organization_id !== $request->user()->organization_id) {
            abort(403);
        }
    }
}
