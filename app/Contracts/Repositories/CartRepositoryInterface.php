<?php

namespace App\Contracts\Repositories;

use App\Models\Cart;
use App\Models\User;

interface CartRepositoryInterface
{
    /**
     * Find a cart by user ID.
     *
     * @param  int  $userId
     * @return Cart|null
     */
    public function findByUserId(int $userId): ?Cart;

    /**
     * Create a cart for a user.
     *
     * @param  int  $userId
     * @return Cart
     */
    public function createForUser(int $userId): Cart;

    /**
     * Find a cart by ID.
     *
     * @param  int  $id
     * @return Cart|null
     */
    public function findById(int $id): ?Cart;

    /**
     * Update a cart.
     *
     * @param  int  $id
     * @param  array<string, mixed>  $data
     * @return bool
     */
    public function update(int $id, array $data): bool;
}

