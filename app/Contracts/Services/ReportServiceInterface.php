<?php

namespace App\Contracts\Services;

interface ReportServiceInterface
{
    /**
     * Generate daily sales report.
     *
     * @return array<string, mixed>
     */
    public function generateDailySalesReport(): array;
}

