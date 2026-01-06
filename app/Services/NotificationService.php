<?php

namespace App\Services;

use App\Contracts\Services\NotificationServiceInterface;
use App\Models\Product;
use App\Notifications\LowStockAlert;
use Illuminate\Support\Facades\Notification;

class NotificationService implements NotificationServiceInterface
{
    /**
     * Send low stock alert notification.
     *
     * @param  Product  $product
     * @return void
     */
    public function sendLowStockAlert(Product $product): void
    {
        $adminEmail = config('mail.admin_email', 'admin@example.com');

        Notification::route('mail', $adminEmail)
            ->notify(new LowStockAlert($product));
    }

    /**
     * Send batched low stock alert notification.
     *
     * @param  array<int, array<string, mixed>>  $products
     * @return void
     */
    public function sendLowStockAlertBatch(array $products): void
    {
        $adminEmail = config('mail.admin_email', 'admin@example.com');

        Notification::route('mail', $adminEmail)
            ->notify(new \App\Notifications\LowStockAlertBatch($products));
    }

    /**
     * Send daily sales report notification.
     *
     * @param  array<string, mixed>  $data
     * @return void
     */
    public function sendDailySalesReport(array $data): void
    {
        $adminEmail = config('mail.admin_email', 'admin@example.com');

        Notification::route('mail', $adminEmail)
            ->notify(new \App\Notifications\DailySalesReport($data));
    }
}
