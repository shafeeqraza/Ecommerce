<?php

namespace App\Observers;

use App\Jobs\LowStockNotification;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        // Only handle manual stock updates (not from cart operations)
        // Check if stock quantity changed and is now low (<= 5)
        if (
            $product->wasChanged('stock_quantity')
            && $product->stock_quantity <= 5
            && $product->getOriginal('stock_quantity') > 5
        ) {
            // Check cache to prevent duplicate notifications
            $cacheKey = "low_stock_notified_{$product->id}";

            if (! Cache::has($cacheKey)) {
                // Set cache flag for 1 hour
                Cache::put($cacheKey, true, now()->addHour());

                LowStockNotification::dispatch($product);
            }
        }
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        //
    }
}
