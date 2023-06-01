<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class productRestockTracking extends Model
{
    protected $table = "productRestockTrackings";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['productRestockId', 'progress', 'userId', 'userUpdateId'];
}
