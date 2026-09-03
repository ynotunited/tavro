<?php

namespace Database\Seeders;

use App\Models\CatalogCategory;
use App\Models\CatalogPack;
use App\Models\CatalogProduct;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Global Nigerian food catalog — shared reference data (type "food") used as
 * type-ahead suggestions and one-tap starter packs, mirroring the existing
 * DrinkCatalogSeeder. Foods, stews and soups ship WITHOUT suggested prices so
 * each merchant sets their own selling price (items import with price 0).
 *
 * Rebuilt idempotently.
 */
class NigerianFoodCatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('catalog_pack_items')
            ->whereIn('catalog_product_id', CatalogProduct::where('type', 'food')->pluck('id'))
            ->delete();
        CatalogProduct::where('type', 'food')->delete();
        CatalogCategory::where('type', 'food')->delete();
        CatalogPack::where('slug', 'nigerian-menu')->delete();

        $categories = $this->categories();
        $catIds = [];
        $order = 0;
        foreach ($categories as $slug => $name) {
            $catIds[$slug] = CatalogCategory::create([
                'name'       => $name,
                'slug'       => $slug,
                'type'       => 'food',
                'sort_order' => $order++,
            ])->id;
        }

        $shelf = [
            'rice-grains' => [
                'Jollof Rice', 'Fried Rice', 'Coconut Rice', 'Native Rice',
                'Ofada Rice', 'White Rice', 'Coconut Jollof', 'Bangalore Rice',
            ],
            'swallows-starches' => [
                'Pounded Yam', 'Amala', 'Eba (Garri)', 'Semo', 'Fufu',
                'Wheat Meal', 'Tuwo Shinkafa', 'Pounded Yam & Efo',
            ],
            'stews-sauces' => [
                'Stewed Beans', 'Egg Stew', 'Tomato Stew', 'Pepper Sauce (Ata Din Din)',
                'Chicken Stew', 'Beef Stew', 'Fish Stew', 'Moi Moi', 'Ewa Agoyin',
            ],
            'soups' => [
                'Egusi Soup', 'Okra Soup', 'Efo Riro', 'Ogbono Soup', 'Banga Soup',
                'Edikang Ikong', 'Afang Soup', 'Pepper Soup', 'Groundnut Soup',
                'Nkwobi', 'Isi Ewu', 'Ofe Nsala',
            ],
            'grilled-oven' => [
                'Grilled Chicken', 'Grilled Fish (Croaker)', 'Asun (Spicy Goat)',
                'Suya', 'Roasted Plantain (Boli)', 'Peppered Gizzard', 'Peppered Snail',
            ],
            'fried-bites' => [
                'Fried Chicken', 'Fried Fish', 'Chicken & Chips', 'Fish & Chips',
                'Puff Puff', 'Chin Chin', 'Scotch Egg', 'Spring Rolls', 'Samosa', 'Meat Pie',
                'Chicken Pie', 'Small Chops Platter',
            ],
            'continental' => [
                'White Rice & Stew', 'Spaghetti Bolognese', 'Jollof Spaghetti',
                'Mac & Cheese', 'Chicken Wings', 'Hamburger', 'Club Sandwich',
                'Shawarma', 'Pizza', 'Fresh Fish Pepper Soup',
            ],
            'breakfast' => [
                'Bread & Egg', 'Fried Yam & Egg', 'Boiled Yam & Egg Sauce',
                'Akara & Pap', 'Oats', 'Cornflakes', 'Indomie & Egg', 'Omelette',
            ],
            'desserts' => [
                'Coconut Candy', 'Chin Chin & Groundnut', 'Small Cake',
                'Fruit Salad', 'Ice Cream',
            ],
        ];

        foreach ($shelf as $slug => $names) {
            foreach ($names as $i => $name) {
                CatalogProduct::create([
                    'catalog_category_id' => $catIds[$slug],
                    'name'                => $name,
                    'brand'               => null,
                    'type'                => 'food',
                    'is_alcoholic'        => false,
                    'description'         => null,
                    'sort_order'          => $i,
                ]);
            }
        }

        // ── Starter pack: the full Nigerian menu ─────────────────────────────
        $pack = CatalogPack::create([
            'name'        => 'Nigerian Menu',
            'slug'        => 'nigerian-menu',
            'type'        => 'food',
            'description' => 'Jollof, stews, soups, swallows and more — the everyday Nigerian restaurant menu. Add them all in one tap, then set your own prices.',
            'sort_order'  => 0,
        ]);

        $order2 = 0;
        $foodProducts = CatalogProduct::where('type', 'food')->get();
        foreach ($foodProducts as $product) {
            $pack->products()->attach($product->id, ['sort_order' => $order2++]);
        }

        $this->command?->info('Nigerian food catalog seeded: '
            . $foodProducts->count() . ' food products, '
            . CatalogCategory::where('type', 'food')->count() . ' categories, 1 pack.');
    }

    private function categories(): array
    {
        return [
            'rice-grains'      => 'Rice & Grains',
            'swallows-starches'=> 'Swallows & Starches',
            'stews-sauces'     => 'Stews & Sauces',
            'soups'            => 'Soups',
            'grilled-oven'     => 'Grilled & Oven',
            'fried-bites'      => 'Fried & Small Chops',
            'continental'      => 'Continental',
            'breakfast'        => 'Breakfast',
            'desserts'         => 'Desserts',
        ];
    }
}
