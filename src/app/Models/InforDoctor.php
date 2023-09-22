<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class InforDoctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'id_doctor',
        'id_department',
        'id_hospital',
        'date_of_birth',
        'experience',
        'gender',
        'search_number',
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

    public function inforHospital() {
        return $this->belongsTo(InforHospital::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

}
