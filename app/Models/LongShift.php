<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LongShift extends Model
{
    protected $table = "long_shifts";

    protected $dates = ['created_at', 'DeletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'locationId',
        'longShiftDate',
        'reason',
        'status',
        'reasonChecker',
        'approvedBy',
        'approvedAt',
        'isDeleted',
        'userId',
        'userUpdateId'
    ];
}
