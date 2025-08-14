<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FullShift extends Model
{
    protected $table = "full_shifts";

    protected $dates = ['created_at', 'DeletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'locationId',
        'fullShiftDate',
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
