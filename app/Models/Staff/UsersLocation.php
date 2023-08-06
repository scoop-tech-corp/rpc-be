<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsersLocation extends Model
{

    protected $table = "usersLocation";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'usersId',
        'locationId',
        'isDeleted',
        'created_at',
        'updated_at'
    ];
}
