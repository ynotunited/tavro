<?php

namespace Database\Seeders;

use App\Models\CatalogCategory;
use App\Models\CatalogPack;
use App\Models\CatalogProduct;
use App\Models\CatalogProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Global drink/bar catalog — shared reference data used as type-ahead
 * suggestions and one-tap starter packs. Rebuilt idempotently.
 *
 * Prices are suggested NGN (editable by merchants), reflecting common
 * bar/parlour retail for 2026. Sizes follow local pack conventions
 * (big/medium/small, bottle/can, 50cl/33cl...).
 */
class DrinkCatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('catalog_pack_items')->truncate();
        DB::table('catalog_packs')->truncate();
        DB::table('catalog_product_variants')->truncate();
        DB::table('catalog_products')->truncate();
        DB::table('catalog_categories')->truncate();

        $categories = $this->categories();
        $catIds = [];
        $order = 0;
        foreach ($categories as $slug => $name) {
            $catIds[$slug] = CatalogCategory::create([
                'name'       => $name,
                'slug'       => $slug,
                'type'       => in_array($slug, ['malt-non-alcoholic', 'soft-drinks']) ? 'soft' : 'drink',
                'sort_order' => $order++,
            ])->id;
        }

        $shelf = [
            'beers-lagers' => [
                ['Star Lager', 'NB Plc', 'drink', true, [
                    ['Bottle 60cl', '60cl bottle', 1400, 950],
                    ['Small Bottle 33cl', '33cl bottle', 800, 500],
                    ['Can 33cl', '33cl can', 950, 600],
                ]],
                ['Gulder Lager', 'NB Plc', 'drink', true, [
                    ['Bottle 60cl', '60cl bottle', 1500, 1000],
                    ['Can 33cl', '33cl can', 1000, 650],
                ]],
                ['33 Export Lager', 'NB Plc', 'drink', true, [
                    ['Big 60cl', '60cl bottle', 1500, 1000],
                    ['Small 33cl', '33cl bottle', 850, 550],
                ]],
                ['Heineken Lager', 'Heineken', 'drink', true, [
                    ['Bottle 60cl', '60cl bottle', 1800, 1250],
                    ['Small 33cl', '33cl bottle', 1050, 700],
                ]],
                ['Heineken Silver', 'Heineken', 'drink', true, [
                    ['Bottle 60cl', '60cl bottle', 2000, 1400],
                    ['Small 33cl', '33cl bottle', 1100, 750],
                ]],
                ['Life Lager', 'NB Plc', 'drink', true, [
                    ['Bottle 60cl', '60cl bottle', 1400, 950],
                    ['Small 33cl', '33cl bottle', 800, 500],
                ]],
                ['Trophy Lager', 'NB Plc', 'drink', true, [
                    ['Bottle 60cl', '60cl bottle', 1300, 850],
                    ['Small 33cl', '33cl bottle', 750, 480],
                ]],
                ['Goldberg Lager', 'NB Plc', 'drink', true, [
                    ['Bottle 60cl', '60cl bottle', 1300, 850],
                    ['Can 33cl', '33cl can', 900, 580],
                ]],
                ['Budweiser', 'Budweiser', 'drink', true, [
                    ['Bottle 60cl', '60cl bottle', 1400, 950],
                    ['Small 33cl', '33cl bottle', 850, 560],
                ]],
                ['Desperados', 'Heineken', 'drink', true, [
                    ['Bottle 65cl', '65cl bottle', 1800, 1250],
                ]],
                ['Star Radler', 'NB Plc', 'drink', true, [
                    ['Can 33cl', '33cl can', 900, 580],
                ]],
            ],
            'stouts-dark' => [
                ['Guinness Foreign Extra Stout', 'Guinness', 'drink', true, [
                    ['Big 60cl', '60cl bottle', 2400, 1650],
                    ['Medium 50cl', '50cl bottle', 2000, 1350],
                    ['Small 33cl', '33cl bottle', 1250, 800],
                ]],
                ['Guinness Smooth', 'Guinness', 'drink', true, [
                    ['Medium 50cl', '50cl bottle', 1700, 1150],
                    ['Small 33cl', '33cl bottle', 1050, 680],
                ]],
                ['Guinness Extra Smooth', 'Guinness', 'drink', true, [
                    ['Medium 50cl', '50cl bottle', 2200, 1500],
                    ['Small 33cl', '33cl bottle', 1250, 820],
                ]],
                ['Legend Extra Stout', 'Guinness', 'drink', true, [
                    ['Big 60cl', '60cl bottle', 1400, 950],
                    ['Small 33cl', '33cl bottle', 800, 520],
                ]],
            ],
            'imported-special' => [
                ['Corona Extra', 'Corona', 'drink', true, [
                    ['Bottle 35.5cl', '35.5cl bottle', 2500, 1800],
                ]],
                ['Carlsberg', 'Carlsberg', 'drink', true, [
                    ['Bottle 50cl', '50cl bottle', 2000, 1400],
                ]],
                ['San Miguel', 'San Miguel', 'drink', true, [
                    ['Bottle 60cl', '60cl bottle', 2200, 1500],
                ]],
                ['Stella Artois', 'Stella Artois', 'drink', true, [
                    ['Bottle 33cl', '33cl bottle', 2000, 1450],
                ]],
                ['Amstel Lager', 'Heineken', 'drink', true, [
                    ['Bottle 60cl', '60cl bottle', 2000, 1400],
                ]],
            ],
            'ciders-rtd' => [
                ['Smirnoff Ice', 'Smirnoff', 'drink', true, [
                    ['Bottle 275ml', '275ml bottle', 1200, 750],
                ]],
                ['Smirnoff Ice Double Black', 'Smirnoff', 'drink', true, [
                    ['Bottle 275ml', '275ml bottle', 1500, 950],
                ]],
                ['Bacardi Breezer', 'Bacardi', 'drink', true, [
                    ['Bottle 275ml', '275ml bottle', 1200, 750],
                ]],
                ['Smirnoff X1', 'Smirnoff', 'drink', true, [
                    ['Can 50cl', '50cl can', 1000, 600],
                ]],
            ],
            'malt-non-alcoholic' => [
                ['Maltina', 'NB Plc', 'drink', false, [
                    ['Big 60cl', '60cl bottle', 700, 420],
                    ['Small 33cl', '33cl bottle', 400, 240],
                ]],
                ['Amstel Malta', 'Heineken', 'drink', false, [
                    ['Big 60cl', '60cl bottle', 700, 420],
                    ['Small 33cl', '33cl bottle', 400, 240],
                ]],
                ['Malta Guinness', 'Guinness', 'drink', false, [
                    ['Big 50cl', '50cl bottle', 800, 500],
                    ['Can 33cl', '33cl can', 600, 360],
                ]],
                ['Hi-Malt', 'Nigeria Breweries', 'drink', false, [
                    ['Bottle 50cl', '50cl bottle', 600, 380],
                ]],
            ],
            'soft-drinks' => [
                ['Coca-Cola', 'Coca-Cola', 'drink', false, [
                    ['Bottle 50cl', '50cl PET bottle', 500, 300],
                    ['Can 33cl', '33cl can', 400, 240],
                ]],
                ['Fanta', 'Coca-Cola', 'drink', false, [
                    ['Bottle 50cl', '50cl PET bottle', 500, 300],
                ]],
                ['Sprite', 'Coca-Cola', 'drink', false, [
                    ['Bottle 50cl', '50cl PET bottle', 500, 300],
                ]],
                ['Schweppes', 'Coca-Cola', 'drink', false, [
                    ['Bottle 50cl', '50cl PET bottle', 500, 300],
                ]],
                ['Chapman (Homemade)', null, 'drink', false, [
                    ['Big 1L', '1 litre', 1200, 500],
                ]],
                ['Zobo', null, 'drink', false, [
                    ['Big 1L', '1 litre', 800, 350],
                ]],
                ['Eva Water', 'Eva', 'drink', false, [
                    ['Bottle 75cl', '75cl bottle', 200, 100],
                    ['Big 1.5L', '1.5L bottle', 300, 150],
                ]],
            ],
            'whisky-scotch' => [
                ['J&B Rare', 'Justerini & Brooks', 'drink', true, [
                    ['Bottle 75cl', '75cl bottle', 16000, 12000],
                    ['Mini 33cl', '33cl bottle', 7000, 5200],
                ]],
                ['Johnnie Walker Red Label', 'Diageo', 'drink', true, [
                    ['Bottle 75cl', '75cl bottle', 22000, 16800],
                    ['Mini 33cl', '33cl bottle', 9500, 7000],
                ]],
                ['Johnnie Walker Black Label', 'Diageo', 'drink', true, [
                    ['Bottle 75cl', '75cl bottle', 40000, 32000],
                ]],
                ['Chivas Regal 12', 'Chivas', 'drink', true, [
                    ['Bottle 70cl', '70cl bottle', 50000, 41000],
                ]],
                ['Old Parr', 'Diageo', 'drink', true, [
                    ['Bottle 75cl', '75cl bottle', 35000, 28000],
                ]],
                ["Grant's", 'Grant\'s', 'drink', true, [
                    ['Bottle 70cl', '70cl bottle', 14000, 10500],
                ]],
            ],
            'gin-vodka' => [
                ["Gordon's London Dry Gin", "Gordon's", 'drink', true, [
                    ['Bottle 75cl', '75cl bottle', 14000, 10500],
                    ['Mini 33cl', '33cl bottle', 5500, 4100],
                ]],
                ['Seagram\'s Gin', 'Seagram', 'drink', true, [
                    ['Bottle 75cl', '75cl bottle', 12000, 9000],
                ]],
                ['Smirnoff Vodka', 'Smirnoff', 'drink', true, [
                    ['Bottle 75cl', '75cl bottle', 9000, 6600],
                    ['Bottle 50cl', '50cl bottle', 6500, 4800],
                ]],
                ['Gilbey\'s London Dry Gin', 'Gilbey', 'drink', true, [
                    ['Bottle 75cl', '75cl bottle', 10000, 7500],
                ]],
            ],
            'rum-brandy' => [
                ['Captain Morgan Spiced Rum', 'Captain Morgan', 'drink', true, [
                    ['Bottle 75cl', '75cl bottle', 15000, 11500],
                ]],
                ['Bacardi Gold', 'Bacardi', 'drink', true, [
                    ['Bottle 75cl', '75cl bottle', 12000, 9000],
                ]],
                ['Bacardi White', 'Bacardi', 'drink', true, [
                    ['Bottle 75cl', '75cl bottle', 12000, 9000],
                ]],
                ['Hennessy V.S', 'Hennessy', 'drink', true, [
                    ['Bottle 70cl', '70cl bottle', 85000, 70000],
                    ['Mini 33cl', '33cl bottle', 40000, 33000],
                ]],
                ['Martell V.S', 'Martell', 'drink', true, [
                    ['Bottle 70cl', '70cl bottle', 50000, 41000],
                ]],
                ['Celliers Brandy', 'Celliers', 'drink', true, [
                    ['Bottle 75cl', '75cl bottle', 9000, 6800],
                ]],
            ],
            'schnapps-bitters' => [
                ["Seaman's Schnapps", "Seaman's", 'drink', true, [
                    ['Big 50cl', '50cl bottle', 6000, 4500],
                    ['Medium 20cl', '20cl bottle', 3200, 2400],
                    ['Small 5cl', '5cl pocket', 1200, 850],
                ]],
                ['Orijin Bitters', 'Guinness', 'drink', true, [
                    ['Big 75cl', '75cl bottle', 6000, 4300],
                    ['Medium 25cl', '25cl bottle', 2600, 1900],
                    ['Small 5cl', '5cl pocket', 900, 620],
                ]],
                ['Angostura Aromatic Bitters', 'Angostura', 'drink', true, [
                    ['Medium 20cl', '20cl bottle', 2500, 1800],
                ]],
                ['Pinto Bitters', 'Pinto', 'drink', true, [
                    ['Medium 20cl', '20cl bottle', 1500, 1000],
                ]],
            ],
            'wines-champagne' => [
                ['4th Street Shiraz', '4th Street', 'drink', true, [
                    ['Bottle 750ml', '750ml bottle', 3500, 2600],
                ]],
                ['4th Street Stein', '4th Street', 'drink', true, [
                    ['Bottle 750ml', '750ml bottle', 3200, 2400],
                ]],
                ['Moët & Chandon Brut', 'Moët & Chandon', 'drink', true, [
                    ['Bottle 750ml', '750ml bottle', 85000, 72000],
                ]],
                ['Martini Rosso', 'Martini', 'drink', true, [
                    ['Bottle 75cl', '75cl bottle', 5000, 3800],
                ]],
            ],
        ];

        // Persist products + variants
        foreach ($shelf as $slug => $products) {
            $i = 0;
            foreach ($products as [$name, $brand, $type, $isAlc, $variants]) {
                $product = CatalogProduct::create([
                    'catalog_category_id' => $catIds[$slug],
                    'name'                => $name,
                    'brand'               => $brand,
                    'type'                => $type,
                    'is_alcoholic'        => $isAlc,
                    'sort_order'          => $i++,
                ]);

                foreach ($variants as $j => [$vName, $sizeLabel, $price, $cost]) {
                    CatalogProductVariant::create([
                        'catalog_product_id'         => $product->id,
                        'name'                       => $vName,
                        'size_label'                 => $sizeLabel,
                        'suggested_selling_price'    => $price,
                        'suggested_cost_price'       => $cost,
                        'sort_order'                 => $j,
                    ]);
                }
            }
        }

        // ── Starter packs ─────────────────────────────────────────────────────
        $beerParlor = CatalogPack::create([
            'name'        => 'Beer Parlour Starter',
            'slug'        => 'beer-parlour-starter',
            'description' => 'Every popular lager, stout, cooler and soft drink a beer parlour stocks. Add the full bar in one tap, then tweak prices.',
            'sort_order'  => 0,
        ]);
        $spiritShelf = CatalogPack::create([
            'name'        => 'Spirit & Bitters Shelf',
            'slug'        => 'spirit-bitters-shelf',
            'description' => 'Whiskies, gins, vodkas, rums, brandies and bitters — the classic spirit shelf.',
            'sort_order'  => 1,
        ]);
        $completeBar = CatalogPack::create([
            'name'        => 'Full Drinks Menu',
            'slug'        => 'full-drinks-menu',
            'description' => 'Everything in the catalog — beers, stouts, spirits, wines and soft drinks.',
            'sort_order'  => 2,
        ]);

        $beerSlugs = ['beers-lagers', 'stouts-dark', 'imported-special', 'ciders-rtd', 'malt-non-alcoholic', 'soft-drinks', 'wines-champagne'];
        $spiritSlugs = ['whisky-scotch', 'gin-vodka', 'rum-brandy', 'schnapps-bitters'];

        $attach = function (CatalogPack $pack, array $catSlugs): void {
            $order = 0;
            foreach ($catSlugs as $slug) {
                $products = CatalogProduct::whereHas('category', fn ($q) => $q->where('slug', $slug))->get();
                foreach ($products as $product) {
                    $pack->products()->attach($product->id, ['sort_order' => $order++]);
                }
            }
        };

        $attach($beerParlor, $beerSlugs);
        $attach($spiritShelf, $spiritSlugs);
        $attach($completeBar, array_merge($beerSlugs, $spiritSlugs));

        $this->command?->info('Drink catalog seeded: '
            . CatalogProduct::count() . ' products, '
            . CatalogProductVariant::count() . ' variants, '
            . CatalogPack::count() . ' packs.');
    }

    private function categories(): array
    {
        return [
            'beers-lagers'          => 'Beers & Lagers',
            'stouts-dark'           => 'Stouts & Dark Beers',
            'imported-special'      => 'Imported & Special Beers',
            'ciders-rtd'            => 'Ciders & RTDs',
            'malt-non-alcoholic'    => 'Malt & Non-Alcoholic',
            'soft-drinks'           => 'Soft Drinks & Water',
            'whisky-scotch'         => 'Whisky & Scotch',
            'gin-vodka'             => 'Gin & Vodka',
            'rum-brandy'            => 'Rum & Brandy',
            'schnapps-bitters'      => 'Schnapps & Bitters',
            'wines-champagne'       => 'Wines & Champagne',
        ];
    }
}