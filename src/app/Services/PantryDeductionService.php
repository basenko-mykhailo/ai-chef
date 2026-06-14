<?php

namespace App\Services;

use App\Models\PantryItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Atomically deducts confirmed quantities from a user's pantry (ticket 4.3).
 * Used by the «Приготовано» flow after the user confirms amounts on the
 * cook-confirmation page (4.2): each matched row is decremented and a row that
 * hits ≤ 0 is removed. Everything runs in one transaction with row locks so a
 * mid-loop failure can't leave the pantry half-deducted.
 */
class PantryDeductionService
{
    /**
     * @param  array<int, array{pantry_item_id: int|string, quantity: int|float|string}>  $items
     */
    public function deduct(User $user, array $items): void
    {
        if ($items === []) {
            return;
        }

        DB::transaction(function () use ($user, $items): void {
            foreach ($items as $row) {
                /** @var PantryItem|null $item */
                $item = $user->pantryItems()
                    ->whereKey($row['pantry_item_id'])
                    ->lockForUpdate()
                    ->first();

                // Ownership is enforced by scoping through $user->pantryItems();
                // skip anything that isn't the user's own or was already removed.
                if ($item === null) {
                    continue;
                }

                $remaining = (float) $item->quantity - (float) $row['quantity'];

                if ($remaining <= 0) {
                    $item->delete();
                } else {
                    $item->update(['quantity' => $remaining]);
                }
            }
        });
    }
}
