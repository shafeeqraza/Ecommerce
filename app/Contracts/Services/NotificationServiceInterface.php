<?php

namespace App\Contracts\Services;

use App\Models\Product;

interface NotificationServiceInterface
{
    /**
     * Send low stock alert notification.
     *
     * @param  Product  $product
     * @return void
     */
    public function sendLowStockAlert(Product $product): void;

    /**
     * Send batched low stock alert notification.
     *
     * @param  array<int, array<string, mixed>>  $products
     * @return void
     */
    public function sendLowStockAlertBatch(array $products): void;

    /**
     * Send daily sales report notification.
     *
     * @param  array<string, mixed>  $data
     * @return void
     */
    public function sendDailySalesReport(array $data): void;
}
