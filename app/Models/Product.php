<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'price',        // This is the selling price
        'purchase_price', // Adding purchase price field
        'unit',
        'img_url',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['profit', 'profit_percentage'];

    /**
     * Get the profit for the product.
     *
     * @return float|null
     */
    public function getProfitAttribute()
    {
        if (is_null($this->purchase_price) || $this->purchase_price == 0) {
            return null;
        }
        
        return round($this->price - $this->purchase_price, 2);
    }

    /**
     * Get the profit percentage for the product.
     *
     * @return float|null
     */
    public function getProfitPercentageAttribute()
    {
        if (is_null($this->purchase_price) || $this->purchase_price == 0) {
            return null;
        }
        
        return round(($this->price - $this->purchase_price) / $this->purchase_price * 100, 2);
    }

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}