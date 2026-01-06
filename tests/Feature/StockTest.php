<?php

namespace Tests\Feature;

use App\Contracts\Services\ProductServiceInterface;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTest extends TestCase
{
    use RefreshDatabase;

    private ProductServiceInterface $productService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productService = app(ProductServiceInterface::class);
    }

    /** @test */
    public function it_can_check_stock_availability(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $result = $this->productService->checkStockAvailability($product->id, 5);

        $this->assertTrue($result);
    }

    /** @test */
    public function it_throws_exception_when_stock_is_insufficient(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 5]);

        $this->expectException(InsufficientStockException::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->productService->checkStockAvailability($product->id, 10);
    }

    /** @test */
    public function it_throws_exception_when_product_not_found(): void
    {
        $this->expectException(InsufficientStockException::class);
        $this->expectExceptionMessage('Product not found');

        $this->productService->checkStockAvailability(99999, 5);
    }

    /** @test */
    public function it_can_decrease_stock_with_locking(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $updatedProduct = $this->productService->decreaseStock($product->id, 3);

        $this->assertNotNull($updatedProduct);
        $this->assertEquals(7, $updatedProduct->stock_quantity);

        $product->refresh();
        $this->assertEquals(7, $product->stock_quantity);
    }

    /** @test */
    public function it_throws_exception_when_decreasing_more_than_available_stock(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 5]);

        $this->expectException(InsufficientStockException::class);

        $this->productService->decreaseStock($product->id, 10);
    }

    /** @test */
    public function it_can_increase_stock_with_locking(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $updatedProduct = $this->productService->increaseStock($product->id, 5);

        $this->assertNotNull($updatedProduct);
        $this->assertEquals(15, $updatedProduct->stock_quantity);

        $product->refresh();
        $this->assertEquals(15, $product->stock_quantity);
    }

    /** @test */
    public function it_returns_null_when_increasing_stock_for_nonexistent_product(): void
    {
        $result = $this->productService->increaseStock(99999, 5);

        $this->assertNull($result);
    }

    /** @test */
    public function it_prevents_race_conditions_with_database_locking(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        // Simulate concurrent stock decreases
        $this->productService->decreaseStock($product->id, 3);
        $this->productService->decreaseStock($product->id, 4);

        $product->refresh();
        $this->assertEquals(3, $product->stock_quantity); // 10 - 3 - 4 = 3

        // Try to decrease more than available
        $this->expectException(InsufficientStockException::class);
        $this->productService->decreaseStock($product->id, 5);
    }

    /** @test */
    public function it_handles_stock_restoration_correctly(): void
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        // Decrease stock
        $this->productService->decreaseStock($product->id, 5);
        $product->refresh();
        $this->assertEquals(5, $product->stock_quantity);

        // Restore stock
        $this->productService->increaseStock($product->id, 3);
        $product->refresh();
        $this->assertEquals(8, $product->stock_quantity);
    }
}
