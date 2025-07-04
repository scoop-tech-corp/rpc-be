<?php

namespace App\Models;

use DB;
use App\Models\Staff\JobTitle;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'password',
        'isDeleted',
        'isLogin'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function getJWTIdentifier()
    {


        $users = DB::table('users')
            ->select(
                'id',
                'email',
                'password',
            )
            ->where([
                ['email', '=', $this->email],
                ['isDeleted', '=', 0]
            ])
            ->first();

        if ($users->password != null) {

            return $this->getKey();
        }
    }
    public function getJWTCustomClaims()
    {
        return [];
    }
    public function products()
    {
        return $this->hasMany(Product::class);
    }
    public function chat()
    {
        return $this->hasMany(Chat::class, 'toUserId', 'id');
    }

    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class, 'jobTitleId');
    }
}
