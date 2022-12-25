<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBundle extends Model
{
    protected $table = "productBundles";

    protected $dates = ['created_at', 'DeletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'name', 'locationId', 'categoryId', 'remark', 'status',
        'userId', 'userUpdateId'
    ];
}
