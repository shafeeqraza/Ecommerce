<?php

namespace App\Repositories;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository implements ProductRepositoryInterface
{
    /**
     * Get all products.
     *
     * @return Collection<int, Product>
     */
    public function findAll(): Collection
    {
        return Product::all();
    }

    /**
     * Find a product by ID.
     *
     * @param  int  $id
     * @return Product|null
     */
    public function findById(int $id): ?Product
    {
        return Product::find($id);
    }

    /**
     * Find products with stock less than or equal to threshold.
     *
     * @param  int  $threshold
     * @return Collection<int, Product>
     */
    public function findByStock(int $threshold): Collection
    {
        return Product::where('stock_quantity', '<=', $threshold)->get();
    }

    /**
     * Update product stock quantity.
     *
     * @param  int  $id
     * @param  int  $quantity
     * @return Product|null
     */
    public function updateStock(int $id, int $quantity): ?Product
    {
        $updated = Product::where('id', $id)->update(['stock_quantity' => $quantity]);

        return $updated ? Product::find($id) : null;
    }

    /**
     * Find a product by ID with pessimistic lock for stock operations.
     *
     * @param  int  $id
     * @return Product|null
     */
    public function findByIdWithLock(int $id): ?Product
    {
        return Product::where('id', $id)->lockForUpdate()->first();
    }

    /**
     * Decrease product stock quantity with lock.
     *
     * @param  int  $id
     * @param  int  $quantity
     * @return Product|null
     */
    public function decreaseStockWithLock(int $id, int $quantity): ?Product
    {
        $product = $this->findByIdWithLock($id);

        if (! $product) {
            return null;
        }

        if ($product->stock_quantity < $quantity) {
            return null;
        }

        $product->decrement('stock_quantity', $quantity);

        return $product->fresh();
    }

    /**
     * Increase product stock quantity with lock.
     *
     * @param  int  $id
     * @param  int  $quantity
     * @return Product|null
     */
    public function increaseStockWithLock(int $id, int $quantity): ?Product
    {
        $product = $this->findByIdWithLock($id);

        if (! $product) {
            return null;
        }

        $product->increment('stock_quantity', $quantity);

        return $product->fresh();
    }
}
