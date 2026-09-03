<?php

namespace App\Services;

use App\Models\CatalogProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;

/**
 * Imports rows from the global (read-only) catalog into a tenant as the
 * tenant's own Product rows. Used by CatalogController (type-ahead add +
 * starter packs) and to auto-populate a fresh menu on registration.
 */
class CatalogImportService
{
    /**
     * Import one catalog product into the org. Returns ['created' => bool, 'product' => Product].
     */
    public function import(int $orgId, CatalogProduct $catalogProduct): array
    {
        $existing = Product::withTrashed()
            ->where('organization_id', $orgId)
            ->whereRaw('lower(name) = ?', [mb_strtolower($catalogProduct->name)])
            ->with(['category', 'variants'])
            ->first();

        if ($existing) {
            return ['created' => false, 'product' => $existing];
        }

        $categoryName = $catalogProduct->category->name ?? 'Miscellaneous';
        $category     = Category::firstOrCreate(
            ['organization_id' => $orgId, 'name' => $categoryName],
            ['sort_order' => 0]
        );

        $defaultPrice = $catalogProduct->variants->first()?->suggested_selling_price ?? 0;
        $defaultCost  = $catalogProduct->variants->first()?->suggested_cost_price ?? 0;

        $product = Product::create([
            'organization_id'    => $orgId,
            'category_id'        => $category->id,
            'name'               => $catalogProduct->name,
            'sku'                => null,
            'description'        => $catalogProduct->description,
            'type'               => $catalogProduct->is_alcoholic || $catalogProduct->type === 'drink' ? 'drink' : 'food',
            'selling_price'      => $defaultPrice,
            'cost_price'         => $defaultCost,
            'is_taxable'         => true,
            'has_service_charge' => true,
            'is_available'       => true,
            'track_inventory'    => false,
            'sort_order'         => $catalogProduct->sort_order,
        ]);

        if ($catalogProduct->variants->isNotEmpty()) {
            $variants = $catalogProduct->variants->values()->map(fn ($v, $i) => [
                'product_id'     => $product->id,
                'name'           => $v->name,
                'sku'            => null,
                'selling_price'  => $v->suggested_selling_price,
                'cost_price'     => $v->suggested_cost_price,
                'is_available'   => true,
                'sort_order'     => $i,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
            ProductVariant::insert($variants->toArray());
        }

        return [
            'created' => true,
            'product' => $product->load(['category', 'variants']),
        ];
    }

    /**
     * Import every global catalog product of the given type into the org.
     * Returns ['added' => int, 'skipped' => int, 'products' => Product[]].
     */
    public function importType(int $orgId, string $type): array
    {
        $products   = CatalogProduct::with(['category', 'variants'])->where('type', $type)->get();
        $existing   = Product::onlyTrashed()
            ->where('organization_id', $orgId)
            ->pluck('name')
            ->map(fn ($n) => mb_strtolower((string) $n))
            ->flip();
        $added      = 0;
        $skipped    = 0;
        $created    = [];

        foreach ($products as $catalogProduct) {
            if ($existing->has(mb_strtolower($catalogProduct->name))) {
                $skipped++;
                continue;
            }

            $result = $this->import($orgId, $catalogProduct);
            if ($result['created']) {
                $added++;
                $existing->put(mb_strtolower($catalogProduct->name), true);
                $created[] = $result['product'];
            } else {
                $skipped++;
            }
        }

        return ['added' => $added, 'skipped' => $skipped, 'products' => $created];
    }
}
