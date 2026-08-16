<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\UnitConversionService;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'price',        // This is the selling price
        'purchase_price', // Adding purchase price field
        'unit_type',
        'stock_quantity',
        'unit',
        'img_url',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['profit', 'profit_percentage', 'stock_display', 'price_label'];

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

    public function getStockDisplayAttribute(): string
    {
        return app(UnitConversionService::class)->formatStock($this->stock_quantity ?? $this->unit ?? 0, $this->unit_type);
    }

    public function getPriceLabelAttribute(): string
    {
        return app(UnitConversionService::class)->formatPriceLabel($this->price, $this->unit_type);
    }

    public function getBaseQuantityAttribute(): int
    {
        return (int) ($this->stock_quantity ?? $this->unit ?? 0);
    }

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}