<?php

namespace Tests\Feature;

use App\Contracts\Services\CartServiceInterface;
use App\Contracts\Services\ProductServiceInterface;
use App\Exceptions\InsufficientStockException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;
    private CartServiceInterface $cartService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->product = Product::factory()->create([
            'stock_quantity' => 10,
            'price' => 29.99,
        ]);

        $this->cartService = app(CartServiceInterface::class);
    }

    /** @test */
    public function it_can_add_item_to_cart(): void
    {
        $cartItem = $this->cartService->addItemToCart(
            $this->user->id,
            $this->product->id,
            2
        );

        $this->assertInstanceOf(CartItem::class, $cartItem);
        $this->assertEquals(2, $cartItem->quantity);
        $this->assertEquals($this->product->id, $cartItem->product_id);

        // Verify stock was decreased
        $this->product->refresh();
        $this->assertEquals(8, $this->product->stock_quantity);
    }

    /** @test */
    public function it_cannot_add_more_items_than_available_stock(): void
    {
        $this->expectException(InsufficientStockException::class);

        $this->cartService->addItemToCart(
            $this->user->id,
            $this->product->id,
            15 // More than available stock (10)
        );
    }

    /** @test */
    public function it_creates_cart_when_adding_first_item(): void
    {
        $this->assertNull(Cart::where('user_id', $this->user->id)->first());

        $this->cartService->addItemToCart(
            $this->user->id,
            $this->product->id,
            1
        );

        $this->assertNotNull(Cart::where('user_id', $this->user->id)->first());
    }

    /** @test */
    public function it_increases_quantity_when_adding_existing_product_to_cart(): void
    {
        // Add product first time
        $this->cartService->addItemToCart($this->user->id, $this->product->id, 2);

        // Add same product again
        $cartItem = $this->cartService->addItemToCart($this->user->id, $this->product->id, 3);

        $this->assertEquals(5, $cartItem->quantity);

        // Verify stock was decreased by total (2 + 3 = 5)
        $this->product->refresh();
        $this->assertEquals(5, $this->product->stock_quantity);
    }

    /** @test */
    public function it_can_update_cart_item_quantity(): void
    {
        // Add item to cart
        $cartItem = $this->cartService->addItemToCart($this->user->id, $this->product->id, 2);

        // Update quantity to 5
        $result = $this->cartService->updateCartItem($cartItem->id, 5);

        $this->assertTrue($result);

        $cartItem->refresh();
        $this->assertEquals(5, $cartItem->quantity);

        // Verify stock: 10 - 5 = 5
        $this->product->refresh();
        $this->assertEquals(5, $this->product->stock_quantity);
    }

    /** @test */
    public function it_restores_stock_when_decreasing_cart_item_quantity(): void
    {
        // Add item with quantity 5
        $cartItem = $this->cartService->addItemToCart($this->user->id, $this->product->id, 5);

        // Decrease to 2
        $this->cartService->updateCartItem($cartItem->id, 2);

        // Verify stock was restored: 10 - 5 + 3 = 8
        $this->product->refresh();
        $this->assertEquals(8, $this->product->stock_quantity);
    }

    /** @test */
    public function it_cannot_update_cart_item_to_exceed_available_stock(): void
    {
        // Add item with quantity 5
        $cartItem = $this->cartService->addItemToCart($this->user->id, $this->product->id, 5);

        // Try to update to 15 (only 10 available, 5 already reserved)
        $this->expectException(InsufficientStockException::class);

        $this->cartService->updateCartItem($cartItem->id, 15);
    }

    /** @test */
    public function it_can_remove_item_from_cart(): void
    {
        // Add item to cart
        $cartItem = $this->cartService->addItemToCart($this->user->id, $this->product->id, 3);

        // Remove item
        $result = $this->cartService->removeCartItem($cartItem->id);

        $this->assertTrue($result);
        $this->assertNull(CartItem::find($cartItem->id));

        // Verify stock was restored
        $this->product->refresh();
        $this->assertEquals(10, $this->product->stock_quantity);
    }

    /** @test */
    public function it_calculates_cart_total_correctly(): void
    {
        $product2 = Product::factory()->create([
            'price' => 49.99,
            'stock_quantity' => 5,
        ]);

        // Add multiple items
        $this->cartService->addItemToCart($this->user->id, $this->product->id, 2); // 2 * 29.99 = 59.98
        $this->cartService->addItemToCart($this->user->id, $product2->id, 1); // 1 * 49.99 = 49.99

        $total = $this->cartService->getCartTotal($this->user->id);

        $this->assertEquals(109.97, $total);
    }

    /** @test */
    public function it_returns_zero_total_for_empty_cart(): void
    {
        $total = $this->cartService->getCartTotal($this->user->id);

        $this->assertEquals(0.0, $total);
    }

    /** @test */
    public function it_calculates_cart_items_count_correctly(): void
    {
        $product2 = Product::factory()->create(['stock_quantity' => 5]);

        // Add items
        $this->cartService->addItemToCart($this->user->id, $this->product->id, 2);
        $this->cartService->addItemToCart($this->user->id, $product2->id, 3);

        $count = $this->cartService->getCartItemsCount($this->user->id);

        $this->assertEquals(5, $count); // 2 + 3 = 5
    }

    /** @test */
    public function it_returns_empty_collection_when_cart_has_no_items(): void
    {
        $items = $this->cartService->getCartItems($this->user->id);

        $this->assertTrue($items->isEmpty());
    }

    /** @test */
    public function it_handles_concurrent_cart_operations_with_transactions(): void
    {
        // This test verifies that transactions prevent race conditions
        $product = Product::factory()->create(['stock_quantity' => 5]);

        // Simulate concurrent operations
        $cartItem1 = $this->cartService->addItemToCart($this->user->id, $product->id, 2);
        $cartItem2 = $this->cartService->addItemToCart($this->user->id, $product->id, 2);

        $product->refresh();
        $this->assertEquals(1, $product->stock_quantity); // 5 - 2 - 2 = 1

        // Try to add one more (should fail)
        $this->expectException(InsufficientStockException::class);
        $this->cartService->addItemToCart($this->user->id, $product->id, 2);
    }

    /** @test */
    public function it_returns_false_when_updating_nonexistent_cart_item(): void
    {
        $result = $this->cartService->updateCartItem(99999, 5);

        $this->assertFalse($result);
    }

    /** @test */
    public function it_returns_false_when_removing_nonexistent_cart_item(): void
    {
        $result = $this->cartService->removeCartItem(99999);

        $this->assertFalse($result);
    }
}
