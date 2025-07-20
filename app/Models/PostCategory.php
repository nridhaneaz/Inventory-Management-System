<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostCategory extends Model
{
    protected $fillable =['name'] ;

     //PostCategory has many posts
     public function posts(){
        return $this->hasMany(Post::class);
    }
}
