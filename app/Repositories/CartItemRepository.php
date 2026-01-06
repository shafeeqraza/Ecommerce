<?php

namespace App\Repositories;

use App\Contracts\Repositories\CartItemRepositoryInterface;
use App\Models\CartItem;
use Illuminate\Database\Eloquent\Collection;

class CartItemRepository implements CartItemRepositoryInterface
{
    /**
     * Find cart items by cart ID.
     *
     * @param  int  $cartId
     * @return Collection<int, CartItem>
     */
    public function findByCartId(int $cartId): Collection
    {
        return CartItem::where('cart_id', $cartId)->with('product')->get();
    }

    /**
     * Create a cart item.
     *
     * @param  array<string, mixed>  $data
     * @return CartItem
     */
    public function create(array $data): CartItem
    {
        return CartItem::create($data);
    }

    /**
     * Update a cart item.
     *
     * @param  int  $id
     * @param  array<string, mixed>  $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        return CartItem::where('id', $id)->update($data);
    }

    /**
     * Delete a cart item.
     *
     * @param  int  $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return CartItem::destroy($id) > 0;
    }

    /**
     * Find a cart item by cart ID and product ID.
     *
     * @param  int  $cartId
     * @param  int  $productId
     * @return CartItem|null
     */
    public function findByCartAndProduct(int $cartId, int $productId): ?CartItem
    {
        return CartItem::where('cart_id', $cartId)
            ->where('product_id', $productId)
            ->first();
    }
}

