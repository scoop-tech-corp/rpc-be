<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsageIdPromotion extends Model
{
    protected $table = "usageIdPromotions";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'usage',
        'userId',
        'userUpdateId'
    ];
}
