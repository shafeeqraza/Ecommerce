<?php

namespace App\Services;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\Services\ProductServiceInterface;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductService implements ProductServiceInterface
{
    /**
     * Create a new product service instance.
     *
     * @param  ProductRepositoryInterface  $productRepository
     * @return void
     */
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository
    ) {}

    /**
     * Get all products.
     *
     * Retrieves all products from the database.
     *
     * @return Collection<int, Product> Collection of all products
     *
     * @example
     * // Get all products
     * $products = $productService->getAllProducts();
     * // Returns: Collection of all Product models
     */
    public function getAllProducts(): Collection
    {
        return $this->productRepository->findAll();
    }

    /**
     * Get a product by ID.
     *
     * Retrieves a single product by its ID.
     *
     * @param  int  $id The product ID
     * @return Product|null The product if found, null otherwise
     *
     * @example
     * // Get product with ID 5
     * $product = $productService->getProductById(5);
     * // Returns: Product model or null if not found
     */
    public function getProductById(int $id): ?Product
    {
        return $this->productRepository->findById($id);
    }

    /**
     * Check if stock is available for a product.
     *
     * Validates that the requested quantity is available in stock.
     * Throws an exception if product is not found or stock is insufficient.
     *
     * @param  int  $productId The ID of the product
     * @param  int  $quantity The quantity to check
     * @return bool True if stock is available
     * @throws InsufficientStockException When product not found or insufficient stock
     *
     * @example
     * // Check if 5 units of product ID 10 are available
     * try {
     *     $productService->checkStockAvailability(10, 5);
     *     // Stock is available
     * } catch (InsufficientStockException $e) {
     *     // Handle insufficient stock
     * }
     */
    public function checkStockAvailability(int $productId, int $quantity): bool
    {
        $product = $this->productRepository->findById($productId);

        if (! $product) {
            throw new InsufficientStockException('Product not found');
        }

        if ($product->stock_quantity < $quantity) {
            throw new InsufficientStockException(
                "Insufficient stock. Available: {$product->stock_quantity}, Requested: {$quantity}"
            );
        }

        return true;
    }

    /**
     * Decrease product stock quantity with database locking.
     *
     * Decreases the stock quantity of a product using pessimistic locking
     * to prevent race conditions. Throws exception if product not found or
     * insufficient stock.
     *
     * @param  int  $productId The ID of the product
     * @param  int  $quantity The quantity to decrease
     * @return Product|null The updated product, or null if operation failed
     * @throws InsufficientStockException When product not found or insufficient stock
     *
     * @example
     * // Decrease stock of product ID 5 by 3 units
     * $product = $productService->decreaseStock(5, 3);
     * // Stock is decreased and product is returned with updated stock_quantity
     */
    public function decreaseStock(int $productId, int $quantity): ?Product
    {
        $product = $this->productRepository->decreaseStockWithLock($productId, $quantity);

        if (! $product) {
            throw new InsufficientStockException('Product not found or insufficient stock');
        }

        return $product;
    }

    /**
     * Increase product stock quantity with database locking.
     *
     * Increases the stock quantity of a product using pessimistic locking
     * to prevent race conditions. Returns null if product not found.
     *
     * @param  int  $productId The ID of the product
     * @param  int  $quantity The quantity to increase
     * @return Product|null The updated product, or null if product not found
     *
     * @example
     * // Increase stock of product ID 5 by 10 units
     * $product = $productService->increaseStock(5, 10);
     * // Stock is increased and product is returned with updated stock_quantity
     */
    public function increaseStock(int $productId, int $quantity): ?Product
    {
        return $this->productRepository->increaseStockWithLock($productId, $quantity);
    }
}
