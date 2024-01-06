<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessControlScheduleDetails extends Model
{
    use HasFactory;

    protected $table = "accessControlSchedulesDetail";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'scheduleMasterId',
        'masterMenuId',
        'listMenuId',
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
