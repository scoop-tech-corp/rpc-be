<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSellReminder extends Model
{
    protected $table = "productSellReminders";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['productSellId', 'unit', 'timing', 'status', 'userId', 'userUpdateId'];
}
