<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductClinicCustomerGroup extends Model
{
    protected $table = "productClinicCustomerGroups";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['productClinicId', 'customerGroupId', 'price', 'userId', 'userUpdateId'];
}
