<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeIdPromotion extends Model
{
    protected $table = "typeIdPromotions";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'typeName',
        'userId',
        'userUpdateId'
    ];
}
