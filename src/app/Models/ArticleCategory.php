<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticleCategory extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'id_article',
        'id_category',
    ];

    public function article() {
        return $this->belongsTo(Article::class);
    }

    public function category() {
        return $this->belongsTo(Category::class);
    }

}
