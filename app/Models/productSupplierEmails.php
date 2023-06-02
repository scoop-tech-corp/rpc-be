<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class productSupplierEmails extends Model
{
    protected $table = "productSupplierEmails";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'productSupplierId', 'usageId', 'address', 'userId', 'userUpdateId'
    ];
}
