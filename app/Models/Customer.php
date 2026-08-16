<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['name', 'email', 'mobile', 'address', 'notes', 'balance_due', 'user_id'];

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    use HasFactory;
}
