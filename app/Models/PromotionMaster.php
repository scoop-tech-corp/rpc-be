<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionMaster extends Model
{
    protected $table = "promotionMasters";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'type',
        'name',
        'startDate',
        'endDate',
        'status',
        'userId',
        'userUpdateId'
    ];
}
