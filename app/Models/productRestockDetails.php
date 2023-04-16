<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class productRestockDetails extends Model
{
    protected $table = "productRestockDetails";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['productRestockId','productId','productType','supplierId','requireDate',
    'reStockQuantity','costPerItem','remark','userId', 'userUpdateId'];
}
