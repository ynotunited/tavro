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
 * Covers the full bar: every common lager/stout, imported & special beers,
 * ciders & RTDs, whisky/scotch, gin, vodka, rum, brandy, cognac, tequila,
 * liqueurs & creams, schnapps & bitters, wines, plus the complete non-alcoholic
 * side (soft drinks, malt, juices & smoothies, energy drinks, water).
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
        foreach ($categories as $slug => [$name, $ctype]) {
            $catIds[$slug] = CatalogCategory::create([
                'name'       => $name,
                'slug'       => $slug,
                'type'       => $ctype, // drink (alcohol) | soft (non-alcohol)
                'sort_order' => $order++,
            ])->id;
        }

        // Persist products + variants
        foreach ($this->shelf() as $slug => $products) {
            $i = 0;
            foreach ($products as [$name, $brand, $isAlc, $variants]) {
                $product = CatalogProduct::create([
                    'catalog_category_id' => $catIds[$slug],
                    'name'                => $name,
                    'brand'               => $brand,
                    'type'                => 'drink',
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
        $attach = function (CatalogPack $pack, array $catSlugs): void {
            $order = 0;
            foreach ($catSlugs as $slug) {
                $products = CatalogProduct::whereHas('category', fn ($q) => $q->where('slug', $slug))->get();
                foreach ($products as $product) {
                    $pack->products()->attach($product->id, ['sort_order' => $order++]);
                }
            }
        };

        $beerSlugs    = ['beers-lagers', 'stouts-dark', 'imported-special', 'ciders-rtd'];
        $softSlugs    = ['soft-drinks', 'malt-non-alcoholic', 'fruit-juices', 'energy-drinks', 'bottled-water'];
        $spiritSlugs  = ['whisky-scotch', 'gin-vodka', 'rum-brandy', 'cognac', 'tequila', 'liqueurs-creams', 'schnapps-bitters'];
        $wineSlugs    = ['wines-champagne'];

        $beerParlor = CatalogPack::create([
            'name'        => 'Beer Parlour Starter',
            'slug'        => 'beer-parlour-starter',
            'type'        => 'drink',
            'description' => 'Every popular lager, stout, cooler, malt and soft drink a beer parlour stocks.',
            'sort_order'  => 0,
        ]);
        $attach($beerParlor, array_merge($beerSlugs, $softSlugs, $wineSlugs));

        $spiritShelf = CatalogPack::create([
            'name'        => 'Spirit & Bitters Shelf',
            'slug'        => 'spirit-bitters-shelf',
            'type'        => 'drink',
            'description' => 'Whiskies, gins, vodkas, rums, brandies, cognac, tequila, liqueurs and bitters.',
            'sort_order'  => 1,
        ]);
        $attach($spiritShelf, $spiritSlugs);

        $softDrinks = CatalogPack::create([
            'name'        => 'Soft Drinks, Malt & Juices',
            'slug'        => 'soft-drinks-malt-juices',
            'type'        => 'drink',
            'description' => 'The complete non-alcoholic side — soft drinks, malt, juices, energy drinks and water.',
            'sort_order'  => 2,
        ]);
        $attach($softDrinks, $softSlugs);

        $fullBar = CatalogPack::create([
            'name'        => 'Full Drinks Menu',
            'slug'        => 'full-drinks-menu',
            'type'        => 'drink',
            'description' => 'The entire bar — beers, stouts, spirits, wines, and every soft drink. Add it all in one tap, then tweak prices.',
            'sort_order'  => 3,
        ]);
        $attach($fullBar, array_merge($beerSlugs, $softSlugs, $spiritSlugs, $wineSlugs));

        $this->command?->info('Drink catalog seeded: '
            . CatalogProduct::count() . ' products, '
            . CatalogProductVariant::count() . ' variants, '
            . CatalogPack::count() . ' packs.');
    }

    /** Slug => [category name, category type] */
    private function categories(): array
    {
        return [
            'beers-lagers'          => ['Beers & Lagers', 'drink'],
            'stouts-dark'           => ['Stouts & Dark Beers', 'drink'],
            'imported-special'      => ['Imported & Special Beers', 'drink'],
            'ciders-rtd'            => ['Ciders & RTDs', 'drink'],
            'whisky-scotch'         => ['Whisky & Scotch', 'drink'],
            'gin-vodka'             => ['Gin & Vodka', 'drink'],
            'rum-brandy'            => ['Rum & Brandy', 'drink'],
            'cognac'                => ['Cognac & Armagnac', 'drink'],
            'tequila'               => ['Tequila & Mezcal', 'drink'],
            'liqueurs-creams'       => ['Liqueurs & Creams', 'drink'],
            'schnapps-bitters'      => ['Schnapps & Bitters', 'drink'],
            'wines-champagne'       => ['Wines & Champagne', 'drink'],
            'malt-non-alcoholic'    => ['Malt & Non-Alcoholic', 'soft'],
            'soft-drinks'           => ['Soft Drinks & Water', 'soft'],
            'fruit-juices'          => ['Fruit Juices & Smoothies', 'soft'],
            'energy-drinks'         => ['Energy Drinks', 'soft'],
            'bottled-water'         => ['Bottled & Sachet Water', 'soft'],
        ];
    }

    /** Slug => [ [name, brand, isAlcoholic, [ [variant, size_label, price, cost] ]] ] */
    private function shelf(): array
    {
        return [
            'beers-lagers' => [
                ['Star Lager', 'NB Plc', true, [['Bottle 60cl', '60cl bottle', 1400, 950], ['Small Bottle 33cl', '33cl bottle', 800, 500], ['Can 33cl', '33cl can', 950, 600]]],
                ['Gulder Lager', 'NB Plc', true, [['Bottle 60cl', '60cl bottle', 1500, 1000], ['Can 33cl', '33cl can', 1000, 650]]],
                ['33 Export Lager', 'NB Plc', true, [['Big 60cl', '60cl bottle', 1500, 1000], ['Small 33cl', '33cl bottle', 850, 550]]],
                ['Heineken Lager', 'Heineken', true, [['Bottle 60cl', '60cl bottle', 1800, 1250], ['Small 33cl', '33cl bottle', 1050, 700]]],
                ['Heineken Silver', 'Heineken', true, [['Bottle 60cl', '60cl bottle', 2000, 1400], ['Small 33cl', '33cl bottle', 1100, 750]]],
                ['Life Lager', 'NB Plc', true, [['Bottle 60cl', '60cl bottle', 1400, 950], ['Small 33cl', '33cl bottle', 800, 500]]],
                ['Trophy Lager', 'NB Plc', true, [['Bottle 60cl', '60cl bottle', 1300, 850], ['Small 33cl', '33cl bottle', 750, 480]]],
                ['Goldberg Lager', 'NB Plc', true, [['Bottle 60cl', '60cl bottle', 1300, 850], ['Can 33cl', '33cl can', 900, 580]]],
                ['Budweiser', 'Budweiser', true, [['Bottle 60cl', '60cl bottle', 1400, 950], ['Small 33cl', '33cl bottle', 850, 560]]],
                ['Desperados', 'Heineken', true, [['Bottle 65cl', '65cl bottle', 1800, 1250]]],
                ['Star Radler', 'NB Plc', true, [['Can 33cl', '33cl can', 900, 580]]],
                ['Camel Export', 'NB Plc', true, [['Bottle 60cl', '60cl bottle', 1500, 1000], ['Small 33cl', '33cl bottle', 850, 550]]],
                ['Eagle Lager', 'Intafact', true, [['Bottle 60cl', '60cl bottle', 1300, 850], ['Small 33cl', '33cl bottle', 750, 480]]],
                ['Castle Milk Stout (clear)', '—', true, [['Bottle 60cl', '60cl bottle', 1700, 1150]]],
                ['Hero Lager', 'Intafact', true, [['Bottle 60cl', '60cl bottle', 1300, 850], ['Small 33cl', '33cl bottle', 750, 480]]],
            ],
            'stouts-dark' => [
                ['Guinness Foreign Extra Stout', 'Guinness', true, [['Big 60cl', '60cl bottle', 2400, 1650], ['Medium 50cl', '50cl bottle', 2000, 1350], ['Small 33cl', '33cl bottle', 1250, 800]]],
                ['Guinness Smooth', 'Guinness', true, [['Medium 50cl', '50cl bottle', 1700, 1150], ['Small 33cl', '33cl bottle', 1050, 680]]],
                ['Guinness Extra Smooth', 'Guinness', true, [['Medium 50cl', '50cl bottle', 2200, 1500], ['Small 33cl', '33cl bottle', 1250, 820]]],
                ['Legend Extra Stout', 'Guinness', true, [['Big 60cl', '60cl bottle', 1400, 950], ['Small 33cl', '33cl bottle', 800, 520]]],
                ['Guinness Extra Smooth (Draught)', 'Guinness', true, [['Can 33cl', '33cl can', 1100, 720]]],
            ],
            'imported-special' => [
                ['Corona Extra', 'Corona', true, [['Bottle 35.5cl', '35.5cl bottle', 2500, 1800]]],
                ['Carlsberg', 'Carlsberg', true, [['Bottle 50cl', '50cl bottle', 2000, 1400]]],
                ['San Miguel', 'San Miguel', true, [['Bottle 60cl', '60cl bottle', 2200, 1500]]],
                ['Stella Artois', 'Stella Artois', true, [['Bottle 33cl', '33cl bottle', 2000, 1450]]],
                ['Amstel Lager', 'Heineken', true, [['Bottle 60cl', '60cl bottle', 2000, 1400]]],
                ['Tuborg Gold', 'Carlsberg', true, [['Bottle 60cl', '60cl bottle', 2000, 1400], ['Can 33cl', '33cl can', 1100, 720]]],
                ['Red Stripe', 'Red Stripe', true, [['Bottle 33cl', '33cl bottle', 2000, 1450]]],
                ['Peak Pale Ale', 'Peak', true, [['Bottle 33cl', '33cl bottle', 1800, 1250]]],
            ],
            'ciders-rtd' => [
                ['Smirnoff Ice', 'Smirnoff', true, [['Bottle 275ml', '275ml bottle', 1200, 750]]],
                ['Smirnoff Ice Double Black', 'Smirnoff', true, [['Bottle 275ml', '275ml bottle', 1500, 950]]],
                ['Bacardi Breezer', 'Bacardi', true, [['Bottle 275ml', '275ml bottle', 1200, 750]]],
                ['Smirnoff X1', 'Smirnoff', true, [['Can 50cl', '50cl can', 1000, 600]]],
                ['Rise Radler', 'NB Plc', true, [['Can 33cl', '33cl can', 700, 450]]],
            ],
            'whisky-scotch' => [
                ['J&B Rare', 'Justerini & Brooks', true, [['Bottle 75cl', '75cl bottle', 16000, 12000], ['Mini 33cl', '33cl bottle', 7000, 5200]]],
                ['Johnnie Walker Red Label', 'Diageo', true, [['Bottle 75cl', '75cl bottle', 22000, 16800], ['Mini 33cl', '33cl bottle', 9500, 7000]]],
                ['Johnnie Walker Black Label', 'Diageo', true, [['Bottle 75cl', '75cl bottle', 40000, 32000]]],
                ['Chivas Regal 12', 'Chivas', true, [['Bottle 70cl', '70cl bottle', 50000, 41000]]],
                ['Old Parr', 'Diageo', true, [['Bottle 75cl', '75cl bottle', 35000, 28000]]],
                ["Grant's", "Grant's", true, [['Bottle 70cl', '70cl bottle', 14000, 10500]]],
                ['Black & White', 'Diageo', true, [['Bottle 75cl', '75cl bottle', 12000, 9000], ['Mini 33cl', '33cl bottle', 5000, 3700]]],
            ],
            'gin-vodka' => [
                ["Gordon's London Dry Gin", "Gordon's", true, [['Bottle 75cl', '75cl bottle', 14000, 10500], ['Mini 33cl', '33cl bottle', 5500, 4100]]],
                ["Seagram's Gin", 'Seagram', true, [['Bottle 75cl', '75cl bottle', 12000, 9000]]],
                ['Beefeater London Dry Gin', 'Beefeater', true, [['Bottle 70cl', '70cl bottle', 18000, 13500]]],
                ['Smirnoff Vodka', 'Smirnoff', true, [['Bottle 75cl', '75cl bottle', 9000, 6600], ['Bottle 50cl', '50cl bottle', 6500, 4800]]],
                ['Gilbey\'s London Dry Gin', 'Gilbey', true, [['Bottle 75cl', '75cl bottle', 10000, 7500]]],
                ["Vermouth Rosso (Martini)", 'Martini', true, [['Bottle 75cl', '75cl bottle', 5000, 3800]]],
                ['Absolut Vodka', 'Absolut', true, [['Bottle 70cl', '70cl bottle', 16000, 12000]]],
            ],
            'rum-brandy' => [
                ['Captain Morgan Spiced Rum', 'Captain Morgan', true, [['Bottle 75cl', '75cl bottle', 15000, 11500]]],
                ['Bacardi Gold', 'Bacardi', true, [['Bottle 75cl', '75cl bottle', 12000, 9000]]],
                ['Bacardi White', 'Bacardi', true, [['Bottle 75cl', '75cl bottle', 12000, 9000]]],
                ['Hennessy V.S', 'Hennessy', true, [['Bottle 70cl', '70cl bottle', 85000, 70000], ['Mini 33cl', '33cl bottle', 40000, 33000]]],
                ['Martell V.S', 'Martell', true, [['Bottle 70cl', '70cl bottle', 50000, 41000]]],
                ['Celliers Brandy', 'Celliers', true, [['Bottle 75cl', '75cl bottle', 9000, 6800]]],
                ['Boursot VSOP Brandy', 'Boursot', true, [['Bottle 75cl', '75cl bottle', 9000, 6800], ['Mini 33cl', '33cl bottle', 4200, 3100]]],
                ['Napoleon Brandy Rare Old', 'Napoleon', true, [['Bottle 75cl', '75cl bottle', 18000, 13500]]],
            ],
            'cognac' => [
                ['Hennessy V.S.O.P', 'Hennessy', true, [['Bottle 70cl', '70cl bottle', 140000, 118000], ['Mini 33cl', '33cl bottle', 65000, 54000]]],
                ['Courvoisier VS', 'Courvoisier', true, [['Bottle 70cl', '70cl bottle', 55000, 45000]]],
                ['Remy Martin VSOP', 'Remy Martin', true, [['Bottle 70cl', '70cl bottle', 130000, 110000]]],
                ['Armagnac Baron Gaston Legrand', 'Baron Gaston', true, [['Bottle 70cl', '70cl bottle', 45000, 36000]]],
            ],
            'tequila' => [
                ['Jose Cuervo Silver', 'Jose Cuervo', true, [['Bottle 70cl', '70cl bottle', 38000, 30000]]],
                ['Jose Cuervo Gold', 'Jose Cuervo', true, [['Bottle 70cl', '70cl bottle', 40000, 31500]]],
                ['Patron Silver', 'Patron', true, [['Bottle 70cl', '70cl bottle', 120000, 100000]]],
                ['Sierra Tequila Silver', 'Sierra', true, [['Bottle 70cl', '70cl bottle', 35000, 27000]]],
            ],
            'liqueurs-creams' => [
                ['Baileys Irish Cream', 'Baileys', true, [['Bottle 70cl', '70cl bottle', 30000, 23500], ['Mini 33cl', '33cl bottle', 13500, 10200]]],
                ['Kahlua Coffee', 'Kahlua', true, [['Bottle 70cl', '70cl bottle', 28000, 22000]]],
                ['Cointreau', 'Cointreau', true, [['Bottle 70cl', '70cl bottle', 32000, 25000]]],
                ['Malibu Coconut', 'Malibu', true, [['Bottle 70cl', '70cl bottle', 24000, 18500]]],
                ['Grand Marnier', 'Grand Marnier', true, [['Bottle 70cl', '70cl bottle', 55000, 44000]]],
                ['Amarula Cream', 'Amarula', true, [['Bottle 70cl', '70cl bottle', 18000, 13800]]],
            ],
            'schnapps-bitters' => [
                ["Seaman's Schnapps", "Seaman's", true, [['Big 50cl', '50cl bottle', 6000, 4500], ['Medium 20cl', '20cl bottle', 3200, 2400], ['Small 5cl', '5cl pocket', 1200, 850]]],
                ['Orijin Bitters', 'Guinness', true, [['Big 75cl', '75cl bottle', 6000, 4300], ['Medium 25cl', '25cl bottle', 2600, 1900], ['Small 5cl', '5cl pocket', 900, 620]]],
                ['Angostura Aromatic Bitters', 'Angostura', true, [['Medium 20cl', '20cl bottle', 2500, 1800]]],
                ['Pinto Bitters', 'Pinto', true, [['Medium 20cl', '20cl bottle', 1500, 1000]]],
                ['Ologbo Bitters', 'Ologbo', true, [['Medium 25cl', '25cl bottle', 3000, 2200]]],
                ['Action Bitters', 'Action', true, [['Medium 12cl', '12cl bottle', 2200, 1600]]],
            ],
            'wines-champagne' => [
                ['4th Street Shiraz', '4th Street', true, [['Bottle 750ml', '750ml bottle', 3500, 2600]]],
                ['4th Street Stein', '4th Street', true, [['Bottle 750ml', '750ml bottle', 3200, 2400]]],
                ['Moët & Chandon Brut', 'Moët & Chandon', true, [['Bottle 750ml', '750ml bottle', 85000, 72000]]],
                ['Martini Rosso', 'Martini', true, [['Bottle 75cl', '75cl bottle', 5000, 3800]]],
                ['Hardys Cabernet Sauvignon', 'Hardys', true, [['Bottle 750ml', '750ml bottle', 9000, 6800]]],
                ['Simonsig Pinotage', 'Simonsig', true, [['Bottle 750ml', '750ml bottle', 7000, 5200]]],
                ['Longridge Chardonnay', 'Longridge', true, [['Bottle 750ml', '750ml bottle', 7500, 5600]]],
                ['Fleur Du Cap Merlot', 'Fleur du Cap', true, [['Bottle 750ml', '750ml bottle', 9000, 6800]]],
                ['Castle Light Rosé', 'Castle', true, [['Bottle 750ml', '750ml bottle', 6000, 4400]]],
                ['Distell Cape View White', 'Cape View', true, [['Bottle 750ml', '750ml bottle', 6500, 4800]]],
            ],
            'malt-non-alcoholic' => [
                ['Maltina', 'NB Plc', false, [['Big 60cl', '60cl bottle', 700, 420], ['Small 33cl', '33cl bottle', 400, 240]]],
                ['Amstel Malta', 'Heineken', false, [['Big 60cl', '60cl bottle', 700, 420], ['Small 33cl', '33cl bottle', 400, 240]]],
                ['Malta Guinness', 'Guinness', false, [['Big 50cl', '50cl bottle', 800, 500], ['Can 33cl', '33cl can', 600, 360]]],
                ['Hi-Malt', 'Nigeria Breweries', false, [['Bottle 50cl', '50cl bottle', 600, 380]]],
                ['Grand Malt', 'Guinness', false, [['Bottle 50cl', '50cl bottle', 700, 440]]],
            ],
            'soft-drinks' => [
                ['Coca-Cola', 'Coca-Cola', false, [['Bottle 50cl', '50cl PET bottle', 500, 300], ['Can 33cl', '33cl can', 400, 240]]],
                ['Fanta', 'Coca-Cola', false, [['Bottle 50cl', '50cl PET bottle', 500, 300]]],
                ['Sprite', 'Coca-Cola', false, [['Bottle 50cl', '50cl PET bottle', 500, 300]]],
                ['Schweppes', 'Coca-Cola', false, [['Bottle 50cl', '50cl PET bottle', 500, 300]]],
                ['Pepsi', 'PepsiCo', false, [['Bottle 50cl', '50cl PET bottle', 500, 300]]],
                ['Mirinda', 'PepsiCo', false, [['Bottle 50cl', '50cl PET bottle', 450, 280]]],
                ['7Up', '7up', false, [['Bottle 50cl', '50cl PET bottle', 500, 300]]],
                ['Mountain Dew', 'PepsiCo', false, [['Bottle 50cl', '50cl PET bottle', 550, 340]]],
                ['Bigi Cola', 'Rite Foods', false, [['Bottle 50cl', '50cl PET bottle', 350, 200]]],
                ['Chapman (Homemade)', null, false, [['Big 1L', '1 litre', 1200, 500]]],
                ['Zobo', null, false, [['Big 1L', '1 litre', 800, 350]]],
                ['Kunu Aya (Tiger Nut)', null, false, [['Big 1L', '1 litre', 1000, 450]]],
                ['La Casera Apple', 'La Casera', false, [['Bottle 33cl', '33cl bottle', 350, 220]]],
                ['5 Alive', null, false, [['Bottle 50cl', '50cl PET', 500, 300]]],
            ],
            'fruit-juices' => [
                ['Chivita 100% Orange', 'Chivita', false, [['Bottle 1L', '1 litre', 1500, 900]]],
                ['Chivita 100% Multivitamin', 'Chivita', false, [['Bottle 1L', '1 litre', 1500, 900]]],
                ['Ribena Blackcurrant', 'Ribena', false, [['Bottle 1L', '1 litre', 1800, 1100]]],
                ['Fresh Orange Juice (Pressed)', null, false, [['Glass 350ml', '350ml glass', 900, 400]]],
                ['Watermelon Juice', null, false, [['Glass 350ml', '350ml glass', 900, 400]]],
                ['Pineapple Juice', null, false, [['Glass 350ml', '350ml glass', 900, 400]]],
                ['Smoothie (Mixed fruit)', null, false, [['Glass 350ml', '350ml glass', 1200, 550]]],
            ],
            'energy-drinks' => [
                ['Monster Energy', 'Monster', false, [['Can 50cl', '50cl can', 1200, 750]]],
                ['Red Bull', 'Red Bull', false, [['Can 25cl', '25cl can', 1500, 950]]],
                ['Sting Energy', 'Sting', false, [['Can 30cl', '30cl can', 400, 250]]],
                ['Fearless Energy', 'Fearless', false, [['Can 25cl', '25cl can', 700, 450]]],
                ['Lucozade Boost (Powerade)', 'Lucozade', false, [['Bottle 50cl', '50cl bottle', 800, 500]]],
            ],
            'bottled-water' => [
                ['Eva Pure Water', 'Eva', false, [['Bottle 75cl', '75cl bottle', 200, 100], ['Big 1.5L', '1.5L bottle', 300, 150]]],
                ['Nestle Pure Life', 'Nestle', false, [['Bottle 75cl', '75cl bottle', 200, 100], ['Big 1.5L', '1.5L bottle', 300, 150]]],
                ['Table Water (Sachet)', 'Local', false, [['Sachet 500ml', '500ml sachet', 50, 25]]],
                ['Sparkling Water', null, false, [['Bottle 75cl', '75cl bottle', 700, 420]]],
            ],
        ];
    }
}
