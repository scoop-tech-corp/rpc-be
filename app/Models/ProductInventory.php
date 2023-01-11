<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductInventory extends Model
{
    protected $table = "productInventories";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'requirementName', 'locationId', 'totalItem', 'isApprovalAdmin', 'isApprovalOffice', 'userId', 'userUpdateId'
    ];
}
