<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSellCustomerGroup extends Model
{
    protected $table = "productSellCustomerGroups";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['productSellId', 'customerGroupId', 'price', 'userId', 'userUpdateId'];
}
