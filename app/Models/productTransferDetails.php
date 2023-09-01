<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class productTransferDetails extends Model
{
    protected $table = "productTransferDetails";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'productTransferId', 'productIdOrigin', 'productIdDestination', 'productType', 'remark', 'quantity',

        'isUserReceived', 'receivedAt', 'reference', 'additionalCost',

        'rejected', 'canceled', 'accepted', 'received', 'reasonCancel',

        'userIdOffice', 'isApprovedOffice', 'reasonOffice', 'officeApprovedAt',

        'isAdminApproval', 'userIdAdmin', 'isApprovedAdmin', 'reasonAdmin', 'adminApprovedAt',

        'userId', 'userUpdateId'
    ];
}
