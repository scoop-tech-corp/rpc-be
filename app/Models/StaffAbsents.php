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
        'presentTime', 'homeTime', 'duration', 'presentLongitude', 'homeLongitude', 'presentLatitude', 'homeLatitude', 'statusPresent', 'statusHome',
        'reasonPresent', 'reasonHome', 'realImageNameHome', 'realImageNamePresent',
        'imagePathPresent', 'imagePathHome', 'cityPresent', 'cityHome', 'provincePresent', 'provinceHome',
        'userId', 'userUpdateId'
    ];
}
