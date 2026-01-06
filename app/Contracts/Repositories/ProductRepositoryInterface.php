<?php

namespace App\Contracts\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    /**
     * Get all products.
     *
     * @return Collection<int, Product>
     */
    public function findAll(): Collection;

    /**
     * Find a product by ID.
     *
     * @param  int  $id
     * @return Product|null
     */
    public function findById(int $id): ?Product;

    /**
     * Find products with stock less than or equal to threshold.
     *
     * @param  int  $threshold
     * @return Collection<int, Product>
     */
    public function findByStock(int $threshold): Collection;

    /**
     * Update product stock quantity.
     *
     * @param  int  $id
     * @param  int  $quantity
     * @return Product|null
     */
    public function updateStock(int $id, int $quantity): ?Product;

    /**
     * Find a product by ID with pessimistic lock for stock operations.
     *
     * @param  int  $id
     * @return Product|null
     */
    public function findByIdWithLock(int $id): ?Product;

    /**
     * Decrease product stock quantity with lock.
     *
     * @param  int  $id
     * @param  int  $quantity
     * @return Product|null
     */
    public function decreaseStockWithLock(int $id, int $quantity): ?Product;

    /**
     * Increase product stock quantity with lock.
     *
     * @param  int  $id
     * @param  int  $quantity
     * @return Product|null
     */
    public function increaseStockWithLock(int $id, int $quantity): ?Product;
}
