<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Doctor extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'id',
        'id_department',
        'id_hospital',
        'email',
        'username',
        'password',
        'name',
        'phone',
        'address',
        'date_of_birth',
        'experience',
        'avatar',
        'gender',
        'is_accept',
        'search_number',
        'role'
    ];

    public function workSchedules()
    {
        return $this->hasMany(WorkSchedule::class);
    }

    public function vacationSchedules()
    {
        return $this->hasMany(VacationSchedule::class);
    }

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function department() {
        return $this->belongsTo(Department::class);
    }

    public function hospital() {
        return $this->belongsTo(Hospital::class);
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
