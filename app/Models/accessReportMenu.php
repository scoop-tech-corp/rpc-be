<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class accessReportMenu extends Model
{
    protected $table = "accessReportMenus";

    protected $dates = ['created_at', 'DeletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'groupName', 'menuName',
        'url', 'roleId', 'accessTypeId', 'userId', 'userUpdateId'
    ];
}
