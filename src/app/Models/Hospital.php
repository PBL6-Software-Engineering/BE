<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Hospital extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'id',
        'email',
        'username',
        'password',
        'is_accept',
        'name',
        'address',
        'infrastructure',
        'description',
        'location',
        'search_number',
        'role'
    ];

    public function doctors()
    {
        return $this->hasMany(Doctor::class);
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

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
