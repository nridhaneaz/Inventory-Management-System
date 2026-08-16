<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    protected $fillable = ['name', 'email', 'password', 'mobile'];

    protected $attributes = [
        'otp' => '0',
    ];

    /**
     * Hash the password when it's being set
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }
}