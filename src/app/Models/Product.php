<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'name',
        'quantity',
        'warranty_period',
        'description',
        'category_id',
        'price',
        'material',
        'dimension',
        'uri',
        'images'
    ];

    // public function importDetails()
    // {
    //     return $this->hasMany(ImportDetail::class);
    // }

    public function category()
    {
        return $this->belongsTo(Category::class);
    } 

    // public function orderDetails()
    // {
    //     return $this->hasMany(OrderDetail::class);
    // }
}
