<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'name',
        'description',
        'search_number',
    ];

    public function doctors()
    {
        return $this->hasMany(Doctor::class);
    }

    public function hospitalDepartments()
    {
        return $this->hasMany(HospitalDepartment::class);
    }
}
