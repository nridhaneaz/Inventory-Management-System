<?php

namespace Tests\Unit;

use App\Http\Controllers\DashboardController;
use PHPUnit\Framework\TestCase;

class DashboardProfitTest extends TestCase
{
    public function test_total_profit_is_zero_when_no_invoice_items_are_sold(): void
    {
        $controller = new DashboardController();

        $this->assertSame(0.0, $controller->calculateProfitForInvoiceItems([]));
    }

    public function test_total_profit_uses_actual_sale_margin_not_stock_margin(): void
    {
        $controller = new DashboardController();

        $items = [
            (object) [
                'sale_price' => 100,
                'qty' => 2,
                'product' => (object) ['purchase_price' => 80],
            ],
            (object) [
                'sale_price' => 70,
                'qty' => 1,
                'product' => (object) ['purchase_price' => 50],
            ],
        ];

        $this->assertSame(60.0, $controller->calculateProfitForInvoiceItems($items));
    }
}
