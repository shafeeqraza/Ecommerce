<?php

namespace App\Contracts\Services;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Database\Eloquent\Collection;

interface CartServiceInterface
{
    /**
     * Get or create a cart for a user.
     *
     * @param  int  $userId
     * @return Cart
     */
    public function getOrCreateCart(int $userId): Cart;

    /**
     * Add an item to the cart.
     *
     * @param  int  $userId
     * @param  int  $productId
     * @param  int  $quantity
     * @return CartItem
     * @throws \App\Exceptions\InsufficientStockException
     */
    public function addItemToCart(int $userId, int $productId, int $quantity): CartItem;

    /**
     * Update a cart item quantity.
     *
     * @param  int  $cartItemId
     * @param  int  $quantity
     * @return bool
     * @throws \App\Exceptions\InsufficientStockException
     */
    public function updateCartItem(int $cartItemId, int $quantity): bool;

    /**
     * Remove a cart item.
     *
     * @param  int  $cartItemId
     * @return bool
     */
    public function removeCartItem(int $cartItemId): bool;

    /**
     * Get cart total for a user.
     *
     * @param  int  $userId
     * @return float
     */
    public function getCartTotal(int $userId): float;

    /**
     * Get cart items count for a user.
     *
     * @param  int  $userId
     * @return int
     */
    public function getCartItemsCount(int $userId): int;

    /**
     * Get cart items for a user.
     *
     * @param  int  $userId
     * @return Collection<int, CartItem>
     */
    public function getCartItems(int $userId): Collection;
}

