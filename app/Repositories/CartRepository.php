<?php

namespace App\Repositories;

use App\Contracts\Repositories\CartRepositoryInterface;
use App\Models\Cart;

class CartRepository implements CartRepositoryInterface
{
    /**
     * Find a cart by user ID.
     *
     * @param  int  $userId
     * @return Cart|null
     */
    public function findByUserId(int $userId): ?Cart
    {
        return Cart::where('user_id', $userId)->first();
    }

    /**
     * Create a cart for a user.
     *
     * @param  int  $userId
     * @return Cart
     */
    public function createForUser(int $userId): Cart
    {
        return Cart::create(['user_id' => $userId]);
    }

    /**
     * Find a cart by ID.
     *
     * @param  int  $id
     * @return Cart|null
     */
    public function findById(int $id): ?Cart
    {
        return Cart::find($id);
    }

    /**
     * Update a cart.
     *
     * @param  int  $id
     * @param  array<string, mixed>  $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        return Cart::where('id', $id)->update($data);
    }
}

