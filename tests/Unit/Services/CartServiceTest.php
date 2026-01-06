<?php

namespace Tests\Unit\Services;

use App\Contracts\Repositories\CartItemRepositoryInterface;
use App\Contracts\Repositories\CartRepositoryInterface;
use App\Contracts\Services\ProductServiceInterface;
use App\Exceptions\InsufficientStockException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    private CartService $cartService;
    private $cartRepository;
    private $cartItemRepository;
    private $productService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cartRepository = Mockery::mock(CartRepositoryInterface::class);
        $this->cartItemRepository = Mockery::mock(CartItemRepositoryInterface::class);
        $this->productService = Mockery::mock(ProductServiceInterface::class);

        $this->cartService = new CartService(
            $this->cartRepository,
            $this->cartItemRepository,
            $this->productService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_gets_existing_cart_for_user(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->create(['user_id' => $user->id]);

        $this->cartRepository
            ->shouldReceive('findByUserId')
            ->once()
            ->with($user->id)
            ->andReturn($cart);

        $result = $this->cartService->getOrCreateCart($user->id);

        $this->assertEquals($cart->id, $result->id);
    }

    #[Test]
    public function it_creates_new_cart_when_none_exists(): void
    {
        $user = User::factory()->create();
        $newCart = Cart::factory()->make(['user_id' => $user->id]);

        $this->cartRepository
            ->shouldReceive('findByUserId')
            ->once()
            ->with($user->id)
            ->andReturn(null);

        $this->cartRepository
            ->shouldReceive('createForUser')
            ->once()
            ->with($user->id)
            ->andReturn($newCart);

        $result = $this->cartService->getOrCreateCart($user->id);

        $this->assertEquals($newCart->id, $result->id);
    }

    #[Test]
    public function it_calculates_cart_total_correctly(): void
    {
        $user = User::factory()->create();
        $cart = Cart::factory()->create(['user_id' => $user->id]);

        $product1 = Product::factory()->create(['price' => 29.99]);
        $product2 = Product::factory()->create(['price' => 49.99]);

        $cartItem1 = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product1->id,
            'quantity' => 2,
        ]);

        $cartItem2 = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product2->id,
            'quantity' => 1,
        ]);

        $cartItems = new Collection([$cartItem1, $cartItem2]);

        $this->cartRepository
            ->shouldReceive('findByUserId')
            ->once()
            ->with($user->id)
            ->andReturn($cart);

        $this->cartItemRepository
            ->shouldReceive('findByCartId')
            ->once()
            ->with($cart->id)
            ->andReturn($cartItems);

        $total = $this->cartService->getCartTotal($user->id);

        // 2 * 29.99 + 1 * 49.99 = 109.97
        $this->assertEquals(109.97, $total);
    }
}
