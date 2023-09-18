<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory;

    protected $fillable = [
        'id',
        'name',
        'email',
        'username',
        'password',
        'address',
        'date_of_birth',
        'gender',
        'phone',
        'is_accept',
        'avatar',
        'google_id',
        'github_id',
        'role',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // protected $hidden = [
    //     'password',
    //     'remember_token',
    // ];


    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function customerOrders()
    {
        return $this->hasMany(CustomerOrder::class);
    }

    // tại một thời điểm nhất định mỗi customer chỉ có một địa chỉ ship hàng  
    public function shippingAddress()
    {
        return $this->hasOne(ShippingAddress::class);
    }
    
}