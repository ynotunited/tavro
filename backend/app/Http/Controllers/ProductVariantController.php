<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    use ApiResponse;

    public function store(Request $request, Product $product)
    {
        $this->authorizeProduct($product, $request);

        $validated = $request->validate([
            'variants'              => 'required|array|min:1',
            'variants.*.name'       => 'required|string|max:255',
            'variants.*.sku'        => 'nullable|string|max:100',
            'variants.*.selling_price' => 'required|numeric|min:0',
            'variants.*.cost_price'    => 'nullable|numeric|min:0',
            'variants.*.is_available'  => 'boolean',
        ]);

        $variants = collect($validated['variants'])->map(function ($v, $i) use ($product) {
            return array_merge($v, ['product_id' => $product->id, 'sort_order' => $i]);
        });

        // Replace all variants
        $product->variants()->delete();
        $created = ProductVariant::insert($variants->toArray());

        return $this->success($product->fresh('variants'), 'Variants saved', 201);
    }

    public function destroy(Request $request, Product $product, ProductVariant $variant)
    {
        $this->authorizeProduct($product, $request);

        if ($variant->product_id !== $product->id) {
            abort(403);
        }

        $variant->delete();

        return $this->success(null, 'Variant deleted');
    }

    private function authorizeProduct(Product $product, Request $request)
    {
        if ($product->organization_id !== $request->user()->organization_id) {
            abort(403);
        }
    }
}
