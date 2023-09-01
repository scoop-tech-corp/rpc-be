<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffAbsents extends Model
{
    protected $table = "staffAbsents";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'presentTime', 'longitude', 'latitude', 'status', 'reason', 'realImageName', 'imagePath', 'address', 'city', 'province',

        'userId', 'userUpdateId'
    ];
}
