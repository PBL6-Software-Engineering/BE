<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'id_doctor',
        'title',
        'content'
    ];

    public function doctor() {
        return $this->belongsTo(Doctor::class);
    }

    public function articleCategories()
    {
        return $this->hasMany(ArticleCategory::class);
    }

}
