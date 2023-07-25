<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessControlSchedule extends Model
{
    use HasFactory;

    protected $table = "accessControlSchedules";

    protected $dates = ['startTime','endTime','created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'usersId',
        'masterId',
        'menuList',
        'accessTypeId',
        'accessLimitId',
        'startTime',
        'endTime',
        'isDeleted',
        'userUpdateId',
        'deletedBy',
        'deletedAt',
        'created_at',
        'updated_at'
    ];
}


