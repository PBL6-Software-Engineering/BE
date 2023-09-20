<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkSchedule extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'id_doctor',
        'id_user',
        'id_service',
        'time',
        'content'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function rating() {
        return $this->hasOne(Rating::class);
    }

    public function doctor() {
        return $this->belongsTo(Doctor::class);
    }

}
