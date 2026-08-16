<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\DailyProfit;
use App\Models\Invoice;
use App\Models\MonthlyProfit;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\UnitConversionService;

class DashboardController extends Controller
{
    public function dashboardPage(Request $request){
        $userId = $request->header('id');
        $converter = app(UnitConversionService::class);
        
        // Get basic dashboard data
        $data = [
            'customer' => Customer::where('user_id', '=', $userId)->count(),
            'category' => Category::where('user_id', '=', $userId)->count(),
            'products' => Product::where('user_id', '=', $userId)->count(),
            'invoice' => Invoice::where('user_id', '=', $userId)->count(),
            'total' => Invoice::where('user_id', '=', $userId)->sum('total'),
            'collection' => Invoice::where('user_id', '=', $userId)->sum('payable'),
            'vat' => Invoice::where('user_id', '=', $userId)->sum('vat')
        ];
        
        // Calculate product stock quantities without treating unsold inventory as profit.
        $products = Product::where('user_id', '=', $userId)->get();
        $weightStock = 0;
        $pieceStock = 0;
        $totalProfit = 0;

        foreach ($products as $product) {
            $baseQuantity = (int) ($product->stock_quantity ?? $product->unit ?? 0);

            if ($converter->isPieceUnit($product->unit_type)) {
                $pieceStock += $baseQuantity;
            } else {
                $weightStock += $baseQuantity;
            }
        }

        $data['weightStock'] = $weightStock;
        $data['pieceStock'] = $pieceStock;
        $data['totalQty'] = $weightStock + $pieceStock;

        $allInvoices = Invoice::with('invoiceProducts.product')
            ->where('user_id', '=', $userId)
            ->get();

        foreach ($allInvoices as $invoice) {
            foreach ($invoice->invoiceProducts as $invoiceProduct) {
                $totalProfit += $this->calculateProfitForInvoiceItems([$invoiceProduct]);
            }
        }

        $data['totalProfit'] = round($totalProfit, 2);
        
        // Get current month and year
        $businessNow = Carbon::now(config('app.business_timezone'));
        $currentMonth = $businessNow->format('F');
        $currentYear = $businessNow->year;
        
        // Calculate current month's profit from invoices directly
        $currentMonthData = $this->calculateCurrentMonthProfit($userId);
        
        // Always update or create the current month's record - even if profit is 0
        $currentMonthProfit = MonthlyProfit::updateOrCreate(
            [
                'user_id' => $userId,
                'month' => $currentMonth,
                'year' => $currentYear
            ],
            [
                'profit_amount' => $currentMonthData['profit'],
                'total_sales' => $currentMonthData['sales']
            ]
        );
        
        // Ensure we have records for the last 12 months (create missing ones with 0 values)
        $this->ensureMonthlyRecordsExist($userId);
        
        // Get monthly profit history (last 12 months) - ordered properly
        $monthlyProfits = MonthlyProfit::where('user_id', '=', $userId)
            ->orderBy('year', 'desc')
            ->orderByRaw("FIELD(month, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December') DESC")
            ->limit(12)
            ->get();
        
        $data['monthlyProfits'] = $monthlyProfits;
        $data['currentMonthProfit'] = $currentMonthProfit;
        
        // ========== DAILY PROFIT SECTION ==========
        
        // Get current date
        $today = Carbon::today(config('app.business_timezone'));
        
        // Calculate today's profit
        $todayData = $this->calculateDayProfit($userId, $today);
        
        // Update or create today's record
        $todayProfit = DailyProfit::updateOrCreate(
            [
                'user_id' => $userId,
                'date' => $today
            ],
            [
                'profit_amount' => $todayData['profit'],
                'total_sales' => $todayData['sales']
            ]
        );
        
        // Ensure we have records for the last 30 days
        $this->ensureDailyRecordsExist($userId);
        
        // Get daily profit history (last 30 days)
        $dailyProfits = DailyProfit::where('user_id', '=', $userId)
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();
        
        $data['dailyProfits'] = $dailyProfits;
        $data['todayProfit'] = $todayProfit;

        return Inertia::render('Dashboard/DashboardPage', ['data' => $data]);
    }

    public function salePage(Request $request){
        $userId = $request->header('id');
        $customers = Customer::where('user_id', '=', $userId)->orderBy('name')->get();
        $products = Product::where('user_id', '=', $userId)->get()->filter(function ($product) {
            return (int) ($product->stock_quantity ?? $product->unit ?? 0) > 0;
        })->values();

        return Inertia::render('Sale/SalePage', [
            'customers' => $customers,
            'products' => $products,
            'business' => config('pos.business'),
        ]);
    }
    
    /**
     * Ensure monthly records exist for the last 12 months
     */
    private function ensureMonthlyRecordsExist($userId)
    {
        $currentDate = Carbon::now(config('app.business_timezone'));
        
        // Create records for the last 12 months
        for ($i = 0; $i < 12; $i++) {
            $targetDate = $currentDate->copy()->subMonths($i);
            $month = $targetDate->format('F');
            $year = $targetDate->year;
            
            // Check if record exists, if not create with 0 values
            $existingRecord = MonthlyProfit::where('user_id', $userId)
                ->where('month', $month)
                ->where('year', $year)
                ->first();
            
            if (!$existingRecord) {
                // Calculate actual profit for this month
                $monthlyData = $this->calculateMonthProfit($userId, $targetDate);
                
                MonthlyProfit::create([
                    'user_id' => $userId,
                    'month' => $month,
                    'year' => $year,
                    'profit_amount' => $monthlyData['profit'],
                    'total_sales' => $monthlyData['sales']
                ]);
            }
        }
    }
    
    /**
     * Ensure daily records exist for the last 30 days
     */
    private function ensureDailyRecordsExist($userId)
    {
        $currentDate = Carbon::now(config('app.business_timezone'));
        
        // Create records for the last 30 days
        for ($i = 0; $i < 30; $i++) {
            $targetDate = $currentDate->copy()->subDays($i);
            $date = $targetDate->toDateString();
            
            // Check if record exists, if not create with calculated values
            $existingRecord = DailyProfit::where('user_id', $userId)
                ->where('date', $date)
                ->first();
            
            if (!$existingRecord) {
                // Calculate actual profit for this day
                $dailyData = $this->calculateDayProfit($userId, $targetDate);
                
                DailyProfit::create([
                    'user_id' => $userId,
                    'date' => $date,
                    'profit_amount' => $dailyData['profit'],
                    'total_sales' => $dailyData['sales']
                ]);
            }
        }
    }
    
    public function calculateProfitForInvoiceItems($invoiceItems)
    {
        $profitAmount = 0.0;

        foreach ($invoiceItems as $invoiceProduct) {
            if ($invoiceProduct->is_custom_item) {
                if ($invoiceProduct->cost_price === null) {
                    continue;
                }

                $profitAmount += (floatval($invoiceProduct->sale_price) - floatval($invoiceProduct->cost_price)) * floatval($invoiceProduct->qty);
                continue;
            }

            $product = $invoiceProduct->product ?? null;
            if (!$product || !isset($product->purchase_price)) {
                continue;
            }

            $salePrice = floatval($invoiceProduct->sale_price ?? 0);
            $purchasePrice = floatval($product->purchase_price ?? 0);
            $qty = floatval($invoiceProduct->qty ?? 0);

            $profitAmount += ($salePrice - $purchasePrice) * $qty;
        }

        return round($profitAmount, 2);
    }

    /**
     * Calculate profit for a specific month
     */
    public function calculateMonthProfit($userId, $targetDate)
    {
        // Specific month date range
        $timezone = config('app.business_timezone');
        $startOfMonth = $targetDate->copy()->setTimezone($timezone)->startOfMonth()->utc();
        $endOfMonth = $targetDate->copy()->setTimezone($timezone)->endOfMonth()->utc();
        
        // Get invoices for this specific month
        $monthInvoices = Invoice::with('invoiceProducts.product')
            ->where('user_id', '=', $userId)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->get();
            
        $monthProfitAmount = 0;
        $monthSales = 0;
        
        foreach ($monthInvoices as $invoice) {
            $monthSales += $invoice->payable;

            $monthProfitAmount += $this->calculateProfitForInvoiceItems($invoice->invoiceProducts);
        }
        
        return [
            'profit' => $monthProfitAmount,
            'sales' => $monthSales
        ];
    }
    
    /**
     * Calculate profit for a specific day
     */
    public function calculateDayProfit($userId, $targetDate)
    {
        // Specific day date range
        $timezone = config('app.business_timezone');
        $startOfDay = $targetDate->copy()->setTimezone($timezone)->startOfDay()->utc();
        $endOfDay = $targetDate->copy()->setTimezone($timezone)->endOfDay()->utc();
        
        // Get invoices for this specific day
        $dayInvoices = Invoice::with('invoiceProducts.product')
            ->where('user_id', '=', $userId)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->get();
            
        $dayProfitAmount = 0;
        $daySales = 0;
        
        foreach ($dayInvoices as $invoice) {
            $daySales += $invoice->payable;

            $dayProfitAmount += $this->calculateProfitForInvoiceItems($invoice->invoiceProducts);
        }
        
        return [
            'profit' => $dayProfitAmount,
            'sales' => $daySales
        ];
    }
    
    // Calculate current month's profit (can be used by other controllers)
    public function calculateCurrentMonthProfit($userId)
    {
        return $this->calculateMonthProfit($userId, Carbon::now(config('app.business_timezone')));
    }
}
