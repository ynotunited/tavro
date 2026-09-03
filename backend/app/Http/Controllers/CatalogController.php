<?php

namespace App\Http\Controllers;

use App\Models\CatalogCategory;
use App\Models\CatalogPack;
use App\Models\CatalogProduct;
use App\Models\Product;
use App\Services\CatalogImportService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

/**
 * Global read-only drink/bar catalog — type-ahead suggestions & starter packs.
 * Catalog rows are shared reference data; "add" creates a tenant-owned copy.
 */
class CatalogController extends Controller
{
    use ApiResponse;

    /** GET /catalog/search?q=&type=&page=&per_page= — browsable, paginated catalog. */
    public function search(Request $request)
    {
        $q       = trim((string) $request->query('q', ''));
        $type    = (string) $request->query('type', ''); // drink | soft | food
        $page    = max(1, (int) $request->query('page', 1));
        $perPage = min(50, max(1, (int) $request->query('per_page', 12)));

        $query = CatalogProduct::query()
            ->with(['category:id,name', 'variants:id,catalog_product_id,name,size_label,suggested_selling_price']);

        // Empty query = "browse everything" for the current type.
        if ($q !== '') {
            $like = '%'.mb_strtolower($q).'%';
            $query->where(function ($w) use ($q, $like) {
                $w->whereRaw('lower(name) like ?', [$like])
                    ->orWhereRaw('lower(brand) like ?', [$like])
                    ->orWhereHas('category', fn ($c) => $c->whereRaw('lower(name) like ?', [$like]))
                    ->orWhereHas('variants', fn ($v) => $v->whereRaw('lower(name) like ?', [$like]))
                    ->orWhereHas('variants', fn ($v) => $v->whereRaw('lower(size_label) like ?', [$like]));
            });
        }

        if (in_array($type, ['drink', 'soft', 'food'], true)) {
            $query->where('type', $type);
        }

        $paginator = $query->orderBy('catalog_category_id')->orderBy('sort_order')
            ->paginate($perPage, ['*'], 'page', $page);

        return $this->success($paginator->items(), null, 200, $this->paginationMeta($paginator));
    }

    /** GET /catalog/categories — auto-complete sources for category names. */
    public function categories(Request $request)
    {
        $type = (string) $request->query('type', ''); // drink | food
        return $this->success(
            CatalogCategory::query()
                ->when(in_array($type, ['drink', 'food'], true), fn ($q) => $q->where('type', $type))
                ->orderBy('sort_order')
                ->withCount('products')
                ->get()
        );
    }

    /** GET /catalog/packs?type=drink|food — one-tap starter packs (with items preview). */
    public function packs(Request $request)
    {
        $type = (string) $request->query('type', ''); // drink | food
        $packs = CatalogPack::query()
            ->when(in_array($type, ['drink', 'food'], true), fn ($q) => $q->where('type', $type))
            ->orderBy('sort_order')
            ->withCount('products')
            ->with(['products' => fn ($q) => $q->select('name')->limit(8)])
            ->get();

        return $this->success($packs);
    }

    /** GET /catalog/packs/{packId}/products?page= — paginated full pack contents. */
    public function packProducts(Request $request, int $packId)
    {
        $pack    = CatalogPack::findOrFail($packId);
        $page    = max(1, (int) $request->query('page', 1));
        $perPage = min(50, max(1, (int) $request->query('per_page', 20)));

        $paginator = $pack->products()
            ->with(['category:id,name', 'variants:id,catalog_product_id,name,size_label,suggested_selling_price'])
            ->paginate($perPage, ['*'], 'page', $page);

        return $this->success($paginator->items(), null, 200, $this->paginationMeta($paginator));
    }

    /** POST /catalog/items/{catalogProductId}/add — import one catalog product into the org. */
    public function add(Request $request, int $catalogProductId)
    {
        $catalogProduct = CatalogProduct::with(['category', 'variants'])->findOrFail($catalogProductId);

        return $this->success(
            app(CatalogImportService::class)->import($request->user()->organization_id, $catalogProduct)
        );
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

            $result = app(CatalogImportService::class)->import($orgId, $catalogProduct);
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

    private function paginationMeta($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
            'per_page'     => $paginator->perPage(),
            'total'        => $paginator->total(),
            'from'         => $paginator->firstItem(),
            'to'           => $paginator->lastItem(),
        ];
    }
}