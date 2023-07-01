<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class productSellBatch extends Model
{
    protected $table = "productSellBatches";

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
