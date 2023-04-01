<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductTransfer extends Model
{
    protected $table = "productTransfers";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'transferNumber', 'transferName','groupData', 'productIdOrigin', 'productIdDestination', 'productType',
        'totalItem', 'userIdReceiver', 'additionalCost', 'remark',

        'isUserReceived', 'receivedAt', 'reference',

        'realImageName', 'imagePath',

        'userIdOffice', 'isApprovedOffice', 'reasonOffice', 'officeApprovedAt',

        'isAdminApproval', 'userIdAdmin', 'isApprovedAdmin', 'reasonAdmin', 'adminApprovedAt',

        'userId', 'userUpdateId'
    ];
}
