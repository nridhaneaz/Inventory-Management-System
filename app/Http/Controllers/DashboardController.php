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
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function dashboardPage(Request $request){
        $userId = $request->header('id');
        
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
        
        // Calculate total product quantity and profit
        $products = Product::where('user_id', '=', $userId)->get();
        $totalQty = 0;
        $totalProfit = 0;
        
        foreach ($products as $product) {
            $totalQty += (int)$product->unit;
            if (isset($product->price) && isset($product->purchase_price)) {
                $profitPerUnit = floatval($product->price) - floatval($product->purchase_price);
                $totalProfit += $profitPerUnit * (int)$product->unit;
            }
        }
        
        $data['totalQty'] = $totalQty;
        $data['totalProfit'] = number_format($totalProfit, 2);
        
        // Get current month and year
        $currentMonth = Carbon::now()->format('F');
        $currentYear = Carbon::now()->year;
        
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
        $today = Carbon::today();
        
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
        $customers = Customer::where('user_id', '=', $userId)->get();
        $products = Product::where('user_id', '=', $userId)->where('unit', '>', 0)->get();

        return Inertia::render('Sale/SalePage', ['customers' => $customers, 'products' => $products]);
    }
    
    /**
     * Ensure monthly records exist for the last 12 months
     */
    private function ensureMonthlyRecordsExist($userId)
    {
        $currentDate = Carbon::now();
        
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
        $currentDate = Carbon::now();
        
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
    
    /**
     * Calculate profit for a specific month
     */
    public function calculateMonthProfit($userId, $targetDate)
    {
        // Specific month date range
        $startOfMonth = $targetDate->copy()->startOfMonth();
        $endOfMonth = $targetDate->copy()->endOfMonth();
        
        // Get invoices for this specific month
        $monthInvoices = Invoice::with('invoiceProducts.product')
            ->where('user_id', '=', $userId)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->get();
            
        $monthProfitAmount = 0;
        $monthSales = 0;
        
        foreach ($monthInvoices as $invoice) {
            $monthSales += $invoice->payable;
            
            foreach ($invoice->invoiceProducts as $invoiceProduct) {
                $product = $invoiceProduct->product;
                if ($product && isset($product->purchase_price)) {
                    $profitPerUnit = floatval($invoiceProduct->sale_price) - floatval($product->purchase_price);
                    $monthProfitAmount += $profitPerUnit * $invoiceProduct->qty;
                }
            }
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
        $startOfDay = $targetDate->copy()->startOfDay();
        $endOfDay = $targetDate->copy()->endOfDay();
        
        // Get invoices for this specific day
        $dayInvoices = Invoice::with('invoiceProducts.product')
            ->where('user_id', '=', $userId)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->get();
            
        $dayProfitAmount = 0;
        $daySales = 0;
        
        foreach ($dayInvoices as $invoice) {
            $daySales += $invoice->payable;
            
            foreach ($invoice->invoiceProducts as $invoiceProduct) {
                $product = $invoiceProduct->product;
                if ($product && isset($product->purchase_price)) {
                    $profitPerUnit = floatval($invoiceProduct->sale_price) - floatval($product->purchase_price);
                    $dayProfitAmount += $profitPerUnit * $invoiceProduct->qty;
                }
            }
        }
        
        return [
            'profit' => $dayProfitAmount,
            'sales' => $daySales
        ];
    }
    
    // Calculate current month's profit (can be used by other controllers)
    public function calculateCurrentMonthProfit($userId)
    {
        return $this->calculateMonthProfit($userId, Carbon::now());
    }
}