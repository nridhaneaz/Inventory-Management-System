<?php

namespace App\Jobs;

use App\Models\MonthlyProfit;
use App\Models\User;
use App\Http\Controllers\DashboardController;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MonthlyProfitJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Monthly Profit Job started');
        
        $currentMonth = Carbon::now()->format('F');
        $currentYear = Carbon::now()->year;
        
        // Get all users
        $users = User::all();
        
        foreach ($users as $user) {
            $this->ensureCurrentMonthRecord($user->id, $currentMonth, $currentYear);
            $this->ensurePreviousMonthsRecords($user->id);
        }
        
        Log::info('Monthly Profit Job completed');
    }
    
    /**
     * Ensure current month record exists
     */
    private function ensureCurrentMonthRecord($userId, $currentMonth, $currentYear)
    {
        $dashboardController = app(DashboardController::class);
        
        // Always update or create current month record
        $profitData = $dashboardController->calculateCurrentMonthProfit($userId);
        
        MonthlyProfit::updateOrCreate(
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
        
        Log::info("Updated monthly profit record for user {$userId} for {$currentMonth} {$currentYear}");
    }
    
    /**
     * Ensure previous months records exist
     */
    private function ensurePreviousMonthsRecords($userId)
    {
        $dashboardController = app(DashboardController::class);
        $currentDate = Carbon::now();
        
        // Check and create records for the last 12 months
        for ($i = 1; $i <= 12; $i++) {
            $targetDate = $currentDate->copy()->subMonths($i);
            $month = $targetDate->format('F');
            $year = $targetDate->year;
            
            // Check if record exists
            $existingRecord = MonthlyProfit::where('user_id', $userId)
                ->where('month', $month)
                ->where('year', $year)
                ->first();
            
            // If no record exists, create one
            if (!$existingRecord) {
                $monthlyData = $dashboardController->calculateMonthProfit($userId, $targetDate);
                
                MonthlyProfit::create([
                    'user_id' => $userId,
                    'month' => $month,
                    'year' => $year,
                    'profit_amount' => $monthlyData['profit'],
                    'total_sales' => $monthlyData['sales']
                ]);
                
                Log::info("Created missing record for user {$userId} for {$month} {$year}");
            }
        }
    }
}