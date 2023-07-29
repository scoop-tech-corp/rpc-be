<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessControlSchedule extends Model
{
    use HasFactory;

    protected $table = "accessControlSchedules";

    protected $dates = ['startTime', 'endTime', 'created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'locationId',
        'usersId',
        'masterId',
        'menuListId',
        'accessTypeId',
        'giveAccessNow',
        'startTime',
        'endTime',
        'status',
        'duration',
        'isDeleted',
        'userUpdateId',
        'createdBy',
        'deletedBy',
        'deletedAt',
        'created_at',
        'updated_at'
    ];
}
