<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timekeeper extends Model
{
    protected $table = "timekeepers";

    protected $dates = ['created_at', 'DeletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'jobTitleId',
        'shiftId',
        'time',
        'userId',
        'userUpdateId'
    ];
}
