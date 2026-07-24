<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryBulkSettingsService
{
    public function __construct(private ActivityLogger $activityLogger) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(array $attributes, User $actor): int
    {
        return DB::transaction(function () use ($attributes, $actor): int {
            $query = InventoryItem::query();
            $this->applyScope($query, $attributes);
            $count = (clone $query)->count();

            if ($count === 0) {
                throw ValidationException::withMessages([
                    'scope' => 'No inventory items match the selected scope.',
                ]);
            }

            $query->update([
                'reorder_level' => $attributes['reorder_level'],
                'target_stock_level' => $attributes['target_stock_level'],
                'updated_at' => now(),
            ]);

            $this->activityLogger->record(
                $actor,
                'inventory-settings.bulk-updated',
                null,
                newValues: [
                    'scope' => $attributes['scope'],
                    'inventory_item_ids' => $attributes['scope'] === 'selected'
                        ? array_values($attributes['inventory_item_ids'])
                        : null,
                    'item_type' => $attributes['item_type'] ?? null,
                    'course_id' => $attributes['course_id'] ?? null,
                    'reorder_level' => $attributes['reorder_level'],
                    'target_stock_level' => $attributes['target_stock_level'],
                    'affected_items' => $count,
                ],
            );

            return $count;
        }, 3);
    }

    /**
     * @param  Builder<InventoryItem>  $query
     * @param  array<string, mixed>  $attributes
     */
    private function applyScope(Builder $query, array $attributes): void
    {
        if ($attributes['scope'] === 'selected') {
            $query->whereKey($attributes['inventory_item_ids']);

            return;
        }

        $query->where('is_active', true);

        if ($attributes['scope'] === 'item_type') {
            $query->where('item_type', $attributes['item_type']);
        }

        if ($attributes['scope'] === 'course') {
            $query->whereHas('pace', fn (Builder $query) => $query->where('course_id', $attributes['course_id']));
        }
    }
}
