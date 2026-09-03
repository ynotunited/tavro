<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeItem;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    use ApiResponse;

    /**
     * Save (or replace) a recipe for a product or variant.
     */
    public function store(Request $request, string $productId)
    {
        $product = Product::find($productId);

        if (! $product) {
            abort(404);
        }

        if ($product->organization_id !== $request->user()->organization_id) {
            abort(403);
        }

        $validated = $request->validate([
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'items'              => 'required|array|min:1',
            'items.*.ingredient_name' => 'required|string|max:255',
            'items.*.quantity'        => 'required|numeric|min:0.0001',
            'items.*.unit'            => 'required|in:unit,ml,g,kg,cl,oz,litre',
        ]);

        // Deactivate old recipe
        Recipe::where('product_id', $product->id)
            ->when($validated['product_variant_id'] ?? null, fn($q, $vid) => $q->where('product_variant_id', $vid))
            ->update(['is_active' => false]);

        // Get next version number
        $lastVersion = Recipe::where('product_id', $product->id)->max('version') ?? 0;

        $recipe = Recipe::create([
            'product_id'         => $product->id,
            'product_variant_id' => $validated['product_variant_id'] ?? null,
            'version'            => $lastVersion + 1,
            'is_active'          => true,
        ]);

        foreach ($validated['items'] as $item) {
            $recipe->items()->create($item);
        }

        return $this->success($recipe->load('items'), 'Recipe saved', 201);
    }

    /**
     * Get the active recipe for a product.
     */
    public function show(Request $request, string $productId)
    {
        $product = Product::find($productId);

        if (! $product) {
            abort(404);
        }

        if ($product->organization_id !== $request->user()->organization_id) {
            abort(403);
        }

        $recipe = Recipe::where('product_id', $product->id)
            ->where('is_active', true)
            ->with('items')
            ->latest()
            ->first();

        return $this->success($recipe);
    }
}
