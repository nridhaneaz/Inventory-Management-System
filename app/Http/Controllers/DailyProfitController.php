<?php

namespace App\Http\Controllers;

use App\Models\DailyProfit;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DailyProfitController extends Controller
{
    /**
     * Update daily profit data for today
     */
    public function updateDailyProfit(Request $request)
    {
        try {
            $userId = $request->header('id');
            $today = Carbon::today();
            
            // Calculate today's profit
            $todayData = $this->calculateDayProfit($userId, $today);
            
            // Update or create today's record
            $dailyProfit = DailyProfit::updateOrCreate(
                [
                    'user_id' => $userId,
                    'date' => $today
                ],
                [
                    'profit_amount' => $todayData['profit'],
                    'total_sales' => $todayData['sales']
                ]
            );
            
            return response()->json([
                'status' => true,
                'message' => 'Daily profit updated successfully',
                'data' => $dailyProfit
            ]);
            
        } catch (\Exception $e) {
            Log::error('Daily profit update failed: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to update daily profit'
            ], 500);
        }
    }
    
    /**
     * Update all daily profits (for historical data)
     */
    public function updateAllDailyProfits(Request $request)
    {
        try {
            $userId = $request->header('id');
            $this->ensureDailyRecordsExist($userId);
            
            return response()->json([
                'status' => true,
                'message' => 'All daily profits updated successfully'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Daily profits update failed: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to update daily profits'
            ], 500);
        }
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
     * Get daily profit history
     */
    public function getDailyProfitHistory(Request $request)
    {
        $userId = $request->header('id');
        $days = $request->get('days', 30); // Default to last 30 days
        
        $dailyProfits = DailyProfit::where('user_id', $userId)
            ->orderBy('date', 'desc')
            ->limit($days)
            ->get();
            
        return response()->json([
            'status' => true,
            'data' => $dailyProfits
        ]);
    }
}