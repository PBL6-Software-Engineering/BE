<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'name',
        'search_number'
    ];
    
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function articleCategories()
    {
        return $this->hasMany(ArticleCategory::class);
    }

}
