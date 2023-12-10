<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class menuProfile extends Model
{
    protected $table = "menuProfiles";

    protected $dates = ['created_at', 'DeletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'title', 'url', 'icon', 'userId', 'userUpdateId'
    ];
}
