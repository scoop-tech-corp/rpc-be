<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class productSupplierTypePhone extends Model
{
    protected $table = "productSupplierTypePhones";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'typeName', 'userId', 'userUpdateId'
    ];
}
