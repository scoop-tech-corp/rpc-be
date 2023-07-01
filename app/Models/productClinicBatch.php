<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class productClinicBatch extends Model
{
    protected $table = "productClinicBatches";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'batchNumber',
        'productId',
        'productRestockId',
        'productTransferId',
        'transferNumber',
        'productRestockDetailId',
        'purchaseRequestNumber',
        'purchaseOrderNumber',
        'expiredDate',
        'sku',
        'userId',
        'userUpdateId'
    ];
}
