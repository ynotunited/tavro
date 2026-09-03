<?php

namespace App\Http\Controllers;

use App\Models\CatalogCategory;
use App\Models\CatalogPack;
use App\Models\CatalogProduct;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

/**
 * Global read-only drink/bar catalog — type-ahead suggestions & starter packs.
 * Catalog rows are shared reference data; "add" creates a tenant-owned copy.
 */
class CatalogController extends Controller
{
    use ApiResponse;

    /** GET /catalog/search?q=&type= — match against name, brand, category & variant names. */
    public function search(Request $request)
    {
        $q    = trim((string) $request->query('q', ''));
        $type = (string) $request->query('type', ''); // drink | soft | food

        if ($q === '') {
            return $this->success([]);
        }

        $query = CatalogProduct::query()
            ->with(['category:id,name', 'variants:id,catalog_product_id,name,size_label,suggested_selling_price'])
            ->where(function ($w) use ($q) {
                $like = '%'.mb_strtolower($q).'%';
                $w->whereRaw('lower(name) like ?', [$like])
                    ->orWhereRaw('lower(brand) like ?', [$like])
                    ->orWhereHas('category', fn ($c) => $c->whereRaw('lower(name) like ?', [$like]))
                    ->orWhereHas('variants', fn ($v) => $v->whereRaw('lower(name) like ?', [$like]))
                    ->orWhereHas('variants', fn ($v) => $v->whereRaw('lower(size_label) like ?', [$like]));
            });

        if (in_array($type, ['drink', 'soft', 'food'], true)) {
            $query->where('type', $type);
        }

        return $this->success($query->orderBy('sort_order')->limit(25)->get());
    }

    /** GET /catalog/categories — auto-complete sources for category names. */
    public function categories()
    {
        return $this->success(
            CatalogCategory::query()->orderBy('sort_order')->withCount('products')->get()
        );
    }

    /** GET /catalog/packs — one-tap starter packs (with items preview). */
    public function packs()
    {
        $packs = CatalogPack::query()
            ->orderBy('sort_order')
            ->withCount('products')
            ->with(['products' => fn ($q) => $q->select('name')->limit(8)])
            ->get();

        return $this->success($packs);
    }

    /** POST /catalog/items/{catalogProductId}/add — import one catalog product into the org. */
    public function add(Request $request, int $catalogProductId)
    {
        $catalogProduct = CatalogProduct::with(['category', 'variants'])->findOrFail($catalogProductId);

        return $this->success($this->import($request->user()->organization_id, $catalogProduct));
    }

    /** POST /catalog/packs/{packId}/apply — import a whole starter pack into the org. */
    public function apply(Request $request, int $packId)
    {
        $pack = CatalogPack::with(['products.category', 'products.variants'])->findOrFail($packId);

        $orgId       = $request->user()->organization_id;
        $existing    = Product::where('organization_id', $orgId)->pluck('name')->map(fn ($n) => mb_strtolower((string) $n))->flip();
        $added       = 0;
        $skipped     = 0;
        $createdRows = [];

        foreach ($pack->products as $catalogProduct) {
            if ($existing->has(mb_strtolower($catalogProduct->name))) {
                $skipped++;
                continue;
            }

            $result = $this->import($orgId, $catalogProduct);
            if ($result['created']) {
                $added++;
                $existing->put(mb_strtolower($catalogProduct->name), true);
                $createdRows[] = $result['product'];
            } else {
                $skipped++;
            }
        }

        return $this->success([
            'added'   => $added,
            'skipped' => $skipped,
            'products'=> $createdRows,
        ], "Imported {$added} products from '{$pack->name}'", 201);
    }

    /**
     * Import a single catalog product as the org's own product + variants.
     * Returns ['created' => bool, 'product' => Product].
     */
    private function import(int $orgId, CatalogProduct $catalogProduct): array
    {
        $existing = Product::where('organization_id', $orgId)
            ->whereRaw('lower(name) = ?', [mb_strtolower($catalogProduct->name)])
            ->with(['category', 'variants'])
            ->first();

        if ($existing) {
            return ['created' => false, 'product' => $existing];
        }

        $categoryName = $catalogProduct->category?->name ?? 'Miscellaneous';
        $category     = Category::firstOrCreate(
            ['organization_id' => $orgId, 'name' => $categoryName],
            ['sort_order' => 0]
        );

        $defaultPrice = $catalogProduct->variants->first()?->suggested_selling_price ?? 0;
        $defaultCost  = $catalogProduct->variants->first()?->suggested_cost_price;

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
}