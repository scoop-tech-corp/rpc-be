<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class childrenMenuGroups extends Model
{
    protected $table = "childrenMenuGroups";

    protected $dates = ['created_at', 'DeletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'groupId', 'orderData', 'menuName',
        'identify', 'title', 'type', 'icon', 'isActive', 'userId', 'userUpdateId'
    ];
}
