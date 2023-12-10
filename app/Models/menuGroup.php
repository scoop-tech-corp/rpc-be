<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class menuGroup extends Model
{
    protected $table = "menuGroups";

    protected $dates = ['created_at', 'DeletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'groupName', 'orderData', 'userId', 'userUpdateId'
    ];
}
