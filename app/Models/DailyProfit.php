<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyProfit extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id',
        'date',
        'profit_amount',
        'total_sales'
    ];
    
    protected $casts = [
        'date' => 'date',
        'profit_amount' => 'decimal:2',
        'total_sales' => 'decimal:2'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}