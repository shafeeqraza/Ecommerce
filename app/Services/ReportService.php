<?php

namespace App\Services;

use App\Contracts\Repositories\CartItemRepositoryInterface;
use App\Contracts\Services\ReportServiceInterface;
use Carbon\Carbon;

class ReportService implements ReportServiceInterface
{
    /**
     * Create a new report service instance.
     *
     * @param  CartItemRepositoryInterface  $cartItemRepository
     * @return void
     */
    public function __construct(
        private readonly CartItemRepositoryInterface $cartItemRepository
    ) {
    }

    /**
     * Generate daily sales report.
     *
     * @return array<string, mixed>
     */
    public function generateDailySalesReport(): array
    {
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();

        // Get all cart items created today
        $cartItems = \App\Models\CartItem::whereBetween('created_at', [$today, $tomorrow])
            ->with('product')
            ->get();

        $report = [
            'date' => $today->format('Y-m-d'),
            'total_items_added' => $cartItems->sum('quantity'),
            'unique_products' => $cartItems->unique('product_id')->count(),
            'products' => [],
        ];

        // Group by product
        $products = $cartItems->groupBy('product_id');

        foreach ($products as $productId => $items) {
            $product = $items->first()->product;
            $totalQuantity = $items->sum('quantity');
            $totalValue = $totalQuantity * $product->price;

            $report['products'][] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $totalQuantity,
                'total_value' => $totalValue,
            ];
        }

        return $report;
    }
}

