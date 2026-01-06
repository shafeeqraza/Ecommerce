<?php

namespace App\Console\Commands;

use App\Contracts\Services\NotificationServiceInterface;
use App\Contracts\Services\ReportServiceInterface;
use Illuminate\Console\Command;

class DailySalesReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:daily-sales';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and send daily sales report';

    /**
     * Create a new command instance.
     *
     * @param  ReportServiceInterface  $reportService
     * @param  NotificationServiceInterface  $notificationService
     * @return void
     */
    public function __construct(
        private readonly ReportServiceInterface $reportService,
        private readonly NotificationServiceInterface $notificationService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating daily sales report...');

        $reportData = $this->reportService->generateDailySalesReport();

        $this->info('Sending daily sales report...');
        $this->notificationService->sendDailySalesReport($reportData);

        $this->info('Daily sales report sent successfully!');

        return Command::SUCCESS;
    }
}
