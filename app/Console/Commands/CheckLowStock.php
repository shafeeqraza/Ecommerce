<?php

namespace App\Console\Commands;

use App\Contracts\Repositories\ProductRepositoryInterface;
use App\Contracts\Services\NotificationServiceInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CheckLowStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:check-low {--threshold=5 : Stock threshold for low stock}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for low stock products and send notifications';

    /**
     * Create a new command instance.
     *
     * @param  ProductRepositoryInterface  $productRepository
     * @param  NotificationServiceInterface  $notificationService
     * @return void
     */
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly NotificationServiceInterface $notificationService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $threshold = (int) $this->option('threshold');

        $this->info("Checking for products with stock <= {$threshold}...");

        // Find all products with low stock
        $lowStockProducts = $this->productRepository->findByStock($threshold);

        $productsToNotify = [];

        foreach ($lowStockProducts as $product) {
            // Use cache to prevent duplicate notifications within 1 hour
            $cacheKey = "low_stock_notified_{$product->id}";

            if (! Cache::has($cacheKey)) {
                $productsToNotify[] = [
                    'name' => $product->name,
                    'stock_quantity' => $product->stock_quantity,
                    'price' => $product->price,
                ];

                // Set cache flag for 1 hour to prevent duplicates
                Cache::put($cacheKey, true, now()->addHour());

                $this->line("  - Found: {$product->name} (Stock: {$product->stock_quantity})");
            }
        }

        if (! empty($productsToNotify)) {
            // Send batched notification
            $this->notificationService->sendLowStockAlertBatch($productsToNotify);

            $this->info("Sent low stock notification for " . count($productsToNotify) . " product(s).");
        } else {
            $this->info("No new low stock notifications needed.");
        }

        return Command::SUCCESS;
    }
}
