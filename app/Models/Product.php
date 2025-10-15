<?php

namespace App\Models;

use App\Models\ProductImage;
use App\Models\ProductRating;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    public function product_images(){
        return $this->hasMany(ProductImage::class);
    }

    public function product_ratings(){
        return $this->hasMany(ProductRating::class)->where('status',1);
    }
}
