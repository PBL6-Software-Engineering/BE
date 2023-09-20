<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HospitalDepartment extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'id_department',
        'id_hospital',
        'time_advise',
        'price',
    ];

    public function department() {
        return $this->belongsTo(Department::class);
    }

    public function hospital() {
        return $this->belongsTo(Hospital::class);
    }

}
