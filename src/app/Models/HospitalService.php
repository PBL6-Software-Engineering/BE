<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HospitalService extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'id_hospital',
        'id_department',
        'name',
        'time_advise',
        'price',
        'infor',
    ];

    public function inforHospital() {
        return $this->belongsTo(InforHospital::class);
    }
    
}
