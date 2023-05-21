<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class productRestockLog extends Model
{
    protected $table = "productRestockLogs";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['productRestockId', 'event', 'details', 'userId', 'userUpdateId'];
}
