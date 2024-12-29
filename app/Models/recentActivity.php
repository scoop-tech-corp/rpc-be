<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class recentActivity extends Model
{
    protected $table = "recentActivities";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'module',
        'event',
        'details',
        'userId',
        'userUpdateId'
    ];
}
