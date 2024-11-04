<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypePhonePromotion extends Model
{
    protected $table = "typePhonePromotions";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'name',
        'userId',
        'userUpdateId'
    ];
}
