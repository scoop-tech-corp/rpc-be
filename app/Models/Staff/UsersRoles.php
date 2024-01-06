<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsersRoles extends Model
{
    use HasFactory;

    protected $table = "usersRoles";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'roleName', 'isActive', 'created_at', 'updated_at'
    ];
}
