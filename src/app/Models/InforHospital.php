<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class InforHospital extends Model
{
    use HasFactory ;

    protected $fillable = [
        'id',
        'id_hospital',
        'province_code',
        'infrastructure',
        'description',
        'location',
        'search_number',
    ];

    public function inforDoctors()
    {
        return $this->hasMany(InforDoctor::class);
    }

    public function hospitalDepartments()
    {
        return $this->hasMany(HospitalDepartment::class);
    }

    public function healthInsuranceHospitals()
    {
        return $this->hasMany(HealthInsuranceHospital::class);
    }

    public function timeWork() {
        return $this->hasOne(TimeWork::class);
    }

    public function hospitalServices()
    {
        return $this->hasMany(HospitalService::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
    
}
