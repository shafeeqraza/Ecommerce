<?php

namespace App\Jobs;

use App\Contracts\Services\NotificationServiceInterface;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class LowStockNotification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param  Product  $product
     * @return void
     */
    public function __construct(
        private readonly Product $product
    ) {
    }

    /**
     * Execute the job.
     *
     * @param  NotificationServiceInterface  $notificationService
     * @return void
     */
    public function handle(NotificationServiceInterface $notificationService): void
    {
        $notificationService->sendLowStockAlert($this->product);
    }
}
