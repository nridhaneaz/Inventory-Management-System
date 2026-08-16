<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceProduct extends Model
{
    protected $fillable=['invoice_id','product_id','user_id','qty','sale_price','quantity','unit','base_quantity','subtotal','is_custom_item','item_name','cost_price','note'];

    public function product(){
        return $this->belongsTo(Product::class);
    }
}
