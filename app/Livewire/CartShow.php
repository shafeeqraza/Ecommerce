<?php

namespace App\Livewire;

use App\Contracts\Services\CartServiceInterface;
use App\Exceptions\InsufficientStockException;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CartShow extends Component
{
    /**
     * Update cart item quantity.
     *
     * @param  int  $cartItemId
     * @param  int  $quantity
     * @param  CartServiceInterface  $cartService
     * @return void
     */
    public function updateQuantity(int $cartItemId, int $quantity, CartServiceInterface $cartService): void
    {
        if ($quantity <= 0) {
            $this->removeItem($cartItemId, $cartService);
            return;
        }

        try {
            $cartService->updateCartItem($cartItemId, $quantity);
            $this->dispatch('cart-updated');
        } catch (InsufficientStockException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Remove item from cart.
     *
     * @param  int  $cartItemId
     * @param  CartServiceInterface  $cartService
     * @return void
     */
    public function removeItem(int $cartItemId, CartServiceInterface $cartService): void
    {
        $cartService->removeCartItem($cartItemId);
        $this->dispatch('cart-updated');
    }

    /**
     * Render the component.
     *
     * @param  CartServiceInterface  $cartService
     * @return \Illuminate\Contracts\View\View
     */
    public function render(CartServiceInterface $cartService)
    {
        $userId = Auth::id();
        $cartItems = $cartService->getCartItems($userId);
        $total = $cartService->getCartTotal($userId);

        return view('livewire.cart-show', [
            'cartItems' => $cartItems,
            'total' => $total,
        ])->layout('layouts.app');
    }
}
