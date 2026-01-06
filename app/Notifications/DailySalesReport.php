<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DailySalesReport extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param  array<string, mixed>  $reportData
     * @return void
     */
    public function __construct(
        private readonly array $reportData
    ) {
    }

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
        $message = (new MailMessage)
            ->subject('Daily Sales Report - ' . $this->reportData['date'])
            ->line('Daily Sales Report')
            ->line("Date: {$this->reportData['date']}")
            ->line("Total Items Added: {$this->reportData['total_items_added']}")
            ->line("Unique Products: {$this->reportData['unique_products']}");

        if (! empty($this->reportData['products'])) {
            $message->line('Products:');
            foreach ($this->reportData['products'] as $product) {
                $message->line(
                    "- {$product['product_name']}: {$product['quantity']} items (Total: $" . number_format($product['total_value'], 2) . ")"
                );
            }
        }

        return $message;
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
