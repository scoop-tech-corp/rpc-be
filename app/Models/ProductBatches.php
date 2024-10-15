<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBatches extends Model
{
    protected $table = "productBatches";

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
