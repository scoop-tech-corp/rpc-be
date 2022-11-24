<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductClinicReminder extends Model
{
    protected $table = "productClinicReminders";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['productClinicId', 'unit', 'timing', 'status', 'userId', 'userUpdateId'];
}
