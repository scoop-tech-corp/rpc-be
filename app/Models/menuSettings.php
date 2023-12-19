<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class menuSettings extends Model
{
    protected $table = "menuSettings";

    protected $dates = ['created_at', 'DeletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'title', 'url', 'icon', 'userId', 'userUpdateId'
    ];
}
