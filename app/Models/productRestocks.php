<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class productRestocks extends Model
{
    protected $table = "productRestocks";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'numberId', 'supplierName', 'locationId', 'variantProduct', 'totalProduct', 'status', 'isAdminApproval',
        'userId', 'userUpdateId'
    ];
}
