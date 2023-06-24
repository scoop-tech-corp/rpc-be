<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class productRestockDetails extends Model
{
    protected $table = "productRestockDetails";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'purchaseRequestNumber', 'purchaseOrderNumber', '', 'productRestockId', 'productId', 'productType', 'supplierId', 'requireDate',
        'currentStock',
        'reStockQuantity', 'rejected', 'canceled', 'accepted', 'received',
        'costPerItem', 'total', 'remark', 'userIdOffice', 'isApprovedOffice', 'reasonOffice', 'officeApprovedAt',
        'isAdminApproval', 'userIdAdmin', 'isApprovedAdmin', 'reasonAdmin', 'adminApprovedAt',
        'userId', 'userUpdateId'
    ];
}
