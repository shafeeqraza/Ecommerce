<?php

namespace App\Services;

use App\Contracts\Repositories\CartItemRepositoryInterface;
use App\Contracts\Repositories\CartRepositoryInterface;
use App\Contracts\Services\CartServiceInterface;
use App\Contracts\Services\ProductServiceInterface;
use App\Exceptions\InsufficientStockException;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CartService implements CartServiceInterface
{
    /**
     * Create a new cart service instance.
     *
     * @param  CartRepositoryInterface  $cartRepository
     * @param  CartItemRepositoryInterface  $cartItemRepository
     * @param  ProductServiceInterface  $productService
     * @return void
     */
    public function __construct(
        private readonly CartRepositoryInterface $cartRepository,
        private readonly CartItemRepositoryInterface $cartItemRepository,
        private readonly ProductServiceInterface $productService
    ) {}

    /**
     * Get or create a cart for a user.
     *
     * Retrieves an existing cart for the user, or creates a new one if none exists.
     *
     * @param  int  $userId The ID of the user
     * @return Cart The user's cart (existing or newly created)
     *
     * @example
     * // Get or create cart for user ID 1
     * $cart = $cartService->getOrCreateCart(1);
     */
    public function getOrCreateCart(int $userId): Cart
    {
        $cart = $this->cartRepository->findByUserId($userId);

        if (! $cart) {
            $cart = $this->cartRepository->createForUser($userId);
        }

        return $cart;
    }

    /**
     * Add an item to the cart.
     *
     * This method adds a product to the user's cart with stock validation and reservation.
     * All operations are wrapped in a database transaction to ensure data consistency.
     *
     * @param  int  $userId The ID of the user adding the item
     * @param  int  $productId The ID of the product to add
     * @param  int  $quantity The quantity to add to the cart
     * @return CartItem The created or updated cart item
     * @throws InsufficientStockException When requested quantity exceeds available stock
     *
     * @example
     * // Add 2 units of product ID 5 to user ID 1's cart
     * $cartItem = $cartService->addItemToCart(1, 5, 2);
     *
     * @example
     * // If product already exists in cart, quantity is increased
     * $cartService->addItemToCart(1, 5, 1); // Adds 1 more, total becomes 3
     */
    public function addItemToCart(int $userId, int $productId, int $quantity): CartItem
    {
        return DB::transaction(function () use ($userId, $productId, $quantity) {
            // Check stock availability
            $this->productService->checkStockAvailability($productId, $quantity);

            // Get or create cart
            $cart = $this->getOrCreateCart($userId);

            // Check if item already exists in cart
            $existingCartItem = $this->cartItemRepository->findByCartAndProduct($cart->id, $productId);

            if ($existingCartItem) {
                // Calculate quantity difference
                $quantityDifference = $quantity; // Adding this much more

                // Check stock availability for the ADDITIONAL quantity needed
                $newQuantity = $existingCartItem->quantity + $quantity;
                $this->productService->checkStockAvailability($productId, $quantityDifference);

                // Decrease stock for the additional quantity
                $this->productService->decreaseStock($productId, $quantityDifference);

                $this->cartItemRepository->update($existingCartItem->id, [
                    'quantity' => $newQuantity,
                ]);

                return $existingCartItem->fresh();
            }

            // Decrease stock for new cart item
            $this->productService->decreaseStock($productId, $quantity);

            // Create new cart item
            return $this->cartItemRepository->create([
                'cart_id' => $cart->id,
                'product_id' => $productId,
                'quantity' => $quantity,
            ]);
        });
    }

    /**
     * Update a cart item quantity.
     *
     * This method updates the quantity of an item in the cart. If increasing quantity,
     * stock is reserved. If decreasing, stock is restored. All operations are wrapped
     * in a database transaction.
     *
     * @param  int  $cartItemId The ID of the cart item to update
     * @param  int  $quantity The new quantity for the cart item
     * @return bool True if update was successful, false if cart item not found
     * @throws InsufficientStockException When requested quantity exceeds available stock
     *
     * @example
     * // Update cart item ID 10 to quantity 5
     * $success = $cartService->updateCartItem(10, 5);
     *
     * @example
     * // Decreasing quantity restores stock automatically
     * $cartService->updateCartItem(10, 2); // Restores 3 units to stock
     */
    public function updateCartItem(int $cartItemId, int $quantity): bool
    {
        return DB::transaction(function () use ($cartItemId, $quantity) {
            $cartItem = CartItem::with('product')->find($cartItemId);

            if (! $cartItem) {
                return false;
            }

            // Get old quantity
            $oldQuantity = $cartItem->quantity;
            $quantityDifference = $quantity - $oldQuantity;

            if ($quantityDifference === 0) {
                return true; // No change
            }

            if ($quantityDifference > 0) {
                // Increasing quantity - check availability for the ADDITIONAL quantity needed
                $this->productService->checkStockAvailability($cartItem->product_id, $quantityDifference);

                // Decrease stock for the additional quantity
                $this->productService->decreaseStock($cartItem->product_id, $quantityDifference);
            } else {
                // Decreasing quantity - restore stock
                $this->productService->increaseStock($cartItem->product_id, abs($quantityDifference));
            }

            return $this->cartItemRepository->update($cartItemId, [
                'quantity' => $quantity,
            ]);
        });
    }

    /**
     * Remove a cart item.
     *
     * This method removes an item from the cart and restores the reserved stock.
     * All operations are wrapped in a database transaction.
     *
     * @param  int  $cartItemId The ID of the cart item to remove
     * @return bool True if removal was successful, false if cart item not found
     *
     * @example
     * // Remove cart item ID 10 from cart
     * $success = $cartService->removeCartItem(10);
     * // Stock is automatically restored
     */
    public function removeCartItem(int $cartItemId): bool
    {
        return DB::transaction(function () use ($cartItemId) {
            $cartItem = CartItem::with('product')->find($cartItemId);

            if (! $cartItem) {
                return false;
            }

            // Restore stock before deleting
            $this->productService->increaseStock($cartItem->product_id, $cartItem->quantity);

            return $this->cartItemRepository->delete($cartItemId);
        });
    }

    /**
     * Get cart total for a user.
     *
     * Calculates the total price of all items in the user's cart.
     *
     * @param  int  $userId The ID of the user
     * @return float The total price of all items in the cart (0.0 if cart is empty)
     *
     * @example
     * // Get total for user ID 1's cart
     * $total = $cartService->getCartTotal(1);
     * // Returns: 99.98 (sum of all item prices * quantities)
     */
    public function getCartTotal(int $userId): float
    {
        $cart = $this->cartRepository->findByUserId($userId);

        if (! $cart) {
            return 0.0;
        }

        $cartItems = $this->cartItemRepository->findByCartId($cart->id);

        return $cartItems->sum(function ($item) {
            return $item->quantity * $item->product->price;
        });
    }

    /**
     * Get cart items count for a user.
     *
     * Returns the total quantity of all items in the user's cart.
     *
     * @param  int  $userId The ID of the user
     * @return int The total quantity of items in the cart (0 if cart is empty)
     *
     * @example
     * // Get total item count for user ID 1
     * $count = $cartService->getCartItemsCount(1);
     * // Returns: 5 (if user has 2 of product A and 3 of product B)
     */
    public function getCartItemsCount(int $userId): int
    {
        $cart = $this->cartRepository->findByUserId($userId);

        if (! $cart) {
            return 0;
        }

        $cartItems = $this->cartItemRepository->findByCartId($cart->id);

        return $cartItems->sum('quantity');
    }

    /**
     * Get cart items for a user.
     *
     * Retrieves all items in the user's cart with their associated products.
     *
     * @param  int  $userId The ID of the user
     * @return Collection<int, CartItem> Collection of cart items (empty if cart is empty)
     *
     * @example
     * // Get all items in user ID 1's cart
     * $items = $cartService->getCartItems(1);
     * // Returns: Collection of CartItem models with product relationships
     */
    public function getCartItems(int $userId): Collection
    {
        $cart = $this->cartRepository->findByUserId($userId);

        if (! $cart) {
            return new Collection;
        }

        return $this->cartItemRepository->findByCartId($cart->id);
    }
}
