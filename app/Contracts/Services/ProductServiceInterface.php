<?php

namespace App\Contracts\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

interface ProductServiceInterface
{
    /**
     * Get all products.
     *
     * @return Collection<int, Product>
     */
    public function getAllProducts(): Collection;

    /**
     * Get a product by ID.
     *
     * @param  int  $id
     * @return Product|null
     */
    public function getProductById(int $id): ?Product;

    /**
     * Check if stock is available for a product.
     *
     * @param  int  $productId
     * @param  int  $quantity
     * @return bool
     * @throws \App\Exceptions\InsufficientStockException
     */
    public function checkStockAvailability(int $productId, int $quantity): bool;

    /**
     * Decrease product stock quantity.
     *
     * @param  int  $productId
     * @param  int  $quantity
     * @return Product|null
     * @throws \App\Exceptions\InsufficientStockException
     */
    public function decreaseStock(int $productId, int $quantity): ?Product;

    /**
     * Increase product stock quantity.
     *
     * @param  int  $productId
     * @param  int  $quantity
     * @return Product|null
     */
    public function increaseStock(int $productId, int $quantity): ?Product;
}
