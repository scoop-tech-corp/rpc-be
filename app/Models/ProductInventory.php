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
        'requirementName', 'locationId', 'isApprovedOffice', 'userApproveOfficeId',
        'userApproveAdminId', 'isApprovedAdmin', 'userApproveOfficeAt', 'userApproveAdminAt',
        'reasonOffice', 'reasonAdmin', 'userId', 'userUpdateId'
    ];
}
