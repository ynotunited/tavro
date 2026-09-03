<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AddOrderItemAction
{
    public function execute(User $actor, Order $order, array $data): OrderItem
    {
        return DB::transaction(function () use ($actor, $order, $data): OrderItem {
            $this->assertOrderAccess($actor, $order);

            $product = Product::query()
                ->whereKey($data['product_id'])
                ->where('organization_id', $order->organization_id)
                ->first();

            if (! $product) {
                throw ValidationException::withMessages([
                    'product_id' => 'The selected product is not available to this organization.',
                ]);
            }

            $variant = null;
            if (! empty($data['product_variant_id'])) {
                $variant = ProductVariant::query()
                    ->whereKey($data['product_variant_id'])
                    ->where('product_id', $product->id)
                    ->first();

                if (! $variant) {
                    throw ValidationException::withMessages([
                        'product_variant_id' => 'The selected variant does not belong to the selected product.',
                    ]);
                }
            }

            if (! $product->is_available) {
                throw ValidationException::withMessages([
                    'product_id' => 'The selected product is currently unavailable.',
                ]);
            }

            $quantity = (int) $data['quantity'];
            $price = $variant ? (float) $variant->selling_price : (float) $product->selling_price;

            $item = OrderItem::create([
                'order_id'           => $order->id,
                'product_id'         => $product->id,
                'product_variant_id' => $variant?->id,
                'product_name'       => $product->name,
                'variant_name'       => $variant?->name,
                'unit_price'         => $price,
                'quantity'           => $quantity,
                'subtotal'           => round($price * $quantity, 2),
                'is_taxable'         => (bool) $product->is_taxable,
                'has_service_charge' => (bool) $product->has_service_charge,
                'notes'              => $data['notes'] ?? null,
                'modifiers'          => $data['modifiers'] ?? null,
                'status'             => 'PENDING',
            ]);

            $order->recalculate();

            return $item->load(['product', 'variant']);
        });
    }

    private function assertOrderAccess(User $actor, Order $order): void
    {
        if ((string) $order->organization_id !== (string) $actor->organization_id
            || (string) $order->branch_id !== (string) $actor->branch_id) {
            abort(403, 'You do not have access to this order.');
        }
    }
}
