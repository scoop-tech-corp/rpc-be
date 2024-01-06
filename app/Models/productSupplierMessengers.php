<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class productSupplierMessengers extends Model
{
    protected $table = "productSupplierMessengers";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'productSupplierId', 'usageId','usageName','typeId', 'userId', 'userUpdateId'
    ];
}
