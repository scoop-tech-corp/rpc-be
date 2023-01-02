<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class usages extends Model
{
    protected $table = "usages";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'usage', 'userId', 'userUpdateId'
    ];
}
