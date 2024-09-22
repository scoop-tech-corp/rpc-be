<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCustomerGroups extends Model
{
    protected $table = "productCustomerGroups";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['productId', 'customerGroupId', 'price', 'userId', 'userUpdateId'];
}
