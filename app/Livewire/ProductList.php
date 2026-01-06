<?php

namespace App\Livewire;

use App\Contracts\Services\CartServiceInterface;
use App\Contracts\Services\ProductServiceInterface;
use App\Exceptions\InsufficientStockException;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ProductList extends Component
{
    /**
     * Add a product to the cart.
     *
     * @param  int  $productId
     * @param  int  $quantity
     * @param  CartServiceInterface  $cartService
     * @return void
     */
    public function addToCart(int $productId, int $quantity = 1, CartServiceInterface $cartService): void
    {
        if (! Auth::check()) {
            session()->flash('error', 'Please login to add items to cart');
            return;
        }

        try {
            $cartService->addItemToCart(Auth::id(), $productId, $quantity);
            session()->flash('success', 'Product added to cart successfully');
            $this->dispatch('cart-updated');
        } catch (InsufficientStockException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    /**
     * Render the component.
     *
     * @param  ProductServiceInterface  $productService
     * @return \Illuminate\Contracts\View\View
     */
    public function render(ProductServiceInterface $productService)
    {
        return view('livewire.product-list', [
            'products' => $productService->getAllProducts(),
        ])->layout('layouts.app');
    }
}
