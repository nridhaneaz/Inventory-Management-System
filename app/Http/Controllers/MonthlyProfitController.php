<?php

namespace App\Http\Controllers;

use App\Models\MonthlyProfit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MonthlyProfitController extends Controller
{
    /**
     * Update the monthly profit data manually
     */
    public function updateMonthlyProfits(Request $request)
    {
        $userId = $request->header('id');
        $currentMonth = Carbon::now()->format('F');
        $currentYear = Carbon::now()->year;
        
        // Calculate profit from all sales this month
        $dashboardController = app(DashboardController::class);
        $profitData = $dashboardController->calculateCurrentMonthProfit($userId);
        
        // Always update or create with fresh data - even if values are 0
        $monthlyProfit = MonthlyProfit::updateOrCreate(
            [
                'user_id' => $userId,
                'month' => $currentMonth,
                'year' => $currentYear
            ],
            [
                'profit_amount' => $profitData['profit'],
                'total_sales' => $profitData['sales']
            ]
        );
        
        // Also ensure we have records for the last few months
        $this->ensurePreviousMonthsExist($userId);
        
        return response()->json([
            'status' => true, 
            'message' => 'Monthly profit data updated successfully for ' . $currentMonth . ' ' . $currentYear,
            'data' => $monthlyProfit,
            'debug' => [
                'current_date' => Carbon::now()->toDateString(),
                'current_month' => $currentMonth,
                'current_year' => $currentYear,
                'calculated_profit' => $profitData['profit'],
                'calculated_sales' => $profitData['sales']
            ]
        ]);
    }
    
    /**
     * Ensure previous months exist with proper data
     */
    private function ensurePreviousMonthsExist($userId)
    {
        $dashboardController = app(DashboardController::class);
        $currentDate = Carbon::now();
        
        // Create/update records for the last 6 months
        for ($i = 1; $i <= 6; $i++) {
            $targetDate = $currentDate->copy()->subMonths($i);
            $month = $targetDate->format('F');
            $year = $targetDate->year;
            
            // Calculate profit for this specific month
            $monthlyData = $dashboardController->calculateMonthProfit($userId, $targetDate);
            
            // Update or create record
            MonthlyProfit::updateOrCreate(
                [
                    'user_id' => $userId,
                    'month' => $month,
                    'year' => $year
                ],
                [
                    'profit_amount' => $monthlyData['profit'],
                    'total_sales' => $monthlyData['sales']
                ]
            );
        }
    }
}