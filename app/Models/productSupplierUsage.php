<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class productSupplierUsage extends Model
{
    protected $table = "productSupplierUsages";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'usageName', 'userId', 'userUpdateId'
    ];
}
