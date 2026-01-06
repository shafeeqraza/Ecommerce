<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockAlertBatch extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param  array<int, array<string, mixed>>  $products
     * @return void
     */
    public function __construct(
        private readonly array $products
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $productCount = count($this->products);
        $message = (new MailMessage)
            ->subject("Low Stock Alert - {$productCount} Product(s)")
            ->line("The following {$productCount} product(s) are running low on stock:");

        foreach ($this->products as $product) {
            $message->line(
                "- {$product['name']}: {$product['stock_quantity']} units (Price: $" . number_format($product['price'], 2) . ")"
            );
        }

        return $message->action('View Products', url('/'))
            ->line('Please restock these products soon.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
