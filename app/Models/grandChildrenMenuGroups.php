<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class grandChildrenMenuGroups extends Model
{
    protected $table = "grandChildrenMenuGroups";

    protected $dates = ['created_at', 'DeletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'childrenId', 'orderMenu', 'menuName',
        'identify', 'title', 'type', 'isActive', 'url', 'userId', 'userUpdateId'
    ];
}
