<?php

namespace App\Contracts\Repositories;

use App\Models\CartItem;
use Illuminate\Database\Eloquent\Collection;

interface CartItemRepositoryInterface
{
    /**
     * Find cart items by cart ID.
     *
     * @param  int  $cartId
     * @return Collection<int, CartItem>
     */
    public function findByCartId(int $cartId): Collection;

    /**
     * Create a cart item.
     *
     * @param  array<string, mixed>  $data
     * @return CartItem
     */
    public function create(array $data): CartItem;

    /**
     * Update a cart item.
     *
     * @param  int  $id
     * @param  array<string, mixed>  $data
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete a cart item.
     *
     * @param  int  $id
     * @return bool
     */
    public function delete(int $id): bool;

    /**
     * Find a cart item by cart ID and product ID.
     *
     * @param  int  $cartId
     * @param  int  $productId
     * @return CartItem|null
     */
    public function findByCartAndProduct(int $cartId, int $productId): ?CartItem;
}

