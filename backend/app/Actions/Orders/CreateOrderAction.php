<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Models\Branch;
use App\Models\Order;
use App\Models\Shift;
use App\Models\Table;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateOrderAction
{
    /**
     * Create an order and update its table atomically.
     *
     * All branch/tenant checks happen inside the transaction so a concurrent
     * POS request cannot create an order against a stale table state.
     */
    public function execute(User $actor, array $data): Order
    {
        if (! $actor->organization_id || ! $actor->branch_id) {
            throw ValidationException::withMessages([
                'branch_id' => 'You must be assigned to a branch before opening orders.',
            ]);
        }

        return DB::transaction(function () use ($actor, $data): Order {
            $branch = Branch::query()
                ->whereKey($actor->branch_id)
                ->where('organization_id', $actor->organization_id)
                ->lockForUpdate()
                ->first();

            if (! $branch) {
                throw ValidationException::withMessages([
                    'branch_id' => 'Your assigned branch is invalid or unavailable.',
                ]);
            }

            $table = null;
            if (isset($data['table_id'])) {
                $table = Table::query()
                    ->whereKey($data['table_id'])
                    ->where('branch_id', $branch->id)
                    ->where('organization_id', $actor->organization_id)
                    ->lockForUpdate()
                    ->first();

                if (! $table) {
                    throw ValidationException::withMessages([
                        'table_id' => 'The selected table does not belong to your branch.',
                    ]);
                }

                if ($table->status !== 'AVAILABLE') {
                    throw ValidationException::withMessages([
                        'table_id' => 'The selected table is not available.',
                    ]);
                }
            }

            $waiterId = $data['waiter_id'] ?? null;
            if ($waiterId !== null) {
                $waiter = User::query()
                    ->whereKey($waiterId)
                    ->where('organization_id', $actor->organization_id)
                    ->where('branch_id', $branch->id)
                    ->first();

                if (! $waiter) {
                    throw ValidationException::withMessages([
                        'waiter_id' => 'The selected waiter is not assigned to this branch.',
                    ]);
                }
            }

            $activeShift = Shift::query()
                ->where('user_id', $actor->id)
                ->where('branch_id', $branch->id)
                ->where('organization_id', $actor->organization_id)
                ->whereIn('status', ['OPEN', 'CLOSING'])
                ->lockForUpdate()
                ->first();

            $order = Order::create([
                'organization_id' => $actor->organization_id,
                'branch_id'       => $branch->id,
                'shift_id'        => $activeShift?->id,
                'table_id'        => $table?->id,
                'cover_count'     => $data['cover_count'] ?? 1,
                'waiter_id'       => $waiterId,
                'opened_by'       => $actor->id,
                'order_number'    => Order::generateOrderNumber($branch->id),
                'status'          => 'OPEN',
                'opened_at'       => now(),
            ]);

            if ($table) {
                $table->update(['status' => 'OCCUPIED']);
            }

            return $order->load(['table', 'waiter', 'items']);
        });
    }
}
