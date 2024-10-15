<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductReminders extends Model
{
    protected $table = "productReminders";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['productId', 'unit', 'timing', 'status', 'userId', 'userUpdateId'];
}
