<?php

namespace App\Livewire;

use App\Contracts\Services\CartServiceInterface;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class CartCounter extends Component
{
    /**
     * Listen for cart updates.
     *
     * @return void
     */
    #[On('cart-updated')]
    public function refresh(): void
    {
        // Component will re-render automatically
    }

    /**
     * Render the component.
     *
     * @param  CartServiceInterface  $cartService
     * @return \Illuminate\Contracts\View\View
     */
    public function render(CartServiceInterface $cartService)
    {
        $count = 0;

        if (Auth::check()) {
            $count = $cartService->getCartItemsCount(Auth::id());
        }

        return view('livewire.cart-counter', [
            'count' => $count,
        ]);
    }
}
