<?php

namespace App\Console\Commands;

use App\Contracts\Services\ProductServiceInterface;
use App\Models\Cart;
use Illuminate\Console\Command;

class ExpireCarts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'carts:expire {--hours=24 : Hours before cart expires}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire old carts and restore stock';

    /**
     * Create a new command instance.
     *
     * @param  ProductServiceInterface  $productService
     * @return void
     */
    public function __construct(
        private readonly ProductServiceInterface $productService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $expiredAt = now()->subHours($hours);

        // Find carts older than specified hours that haven't been updated recently
        $expiredCarts = Cart::where('created_at', '<', $expiredAt)
            ->whereDoesntHave('cartItems', function ($query) use ($hours) {
                $query->where('updated_at', '>', now()->subHours($hours));
            })
            ->with('cartItems.product')
            ->get();

        $restoredCount = 0;
        $deletedCarts = 0;

        foreach ($expiredCarts as $cart) {
            foreach ($cart->cartItems as $cartItem) {
                // Restore stock
                $this->productService->increaseStock($cartItem->product_id, $cartItem->quantity);
                $restoredCount += $cartItem->quantity;
            }

            // Delete cart items and cart
            $cart->cartItems()->delete();
            $cart->delete();
            $deletedCarts++;
        }

        if ($deletedCarts > 0) {
            $this->info("Expired {$deletedCarts} cart(s) and restored {$restoredCount} item(s) to stock.");
        } else {
            $this->info("No expired carts found.");
        }

        return Command::SUCCESS;
    }
}
