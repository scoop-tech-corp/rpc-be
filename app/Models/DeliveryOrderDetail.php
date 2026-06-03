<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryOrderDetail extends Model
{
    protected $table = 'deliveryOrderDetails';

    protected $guarded = ['id'];

    protected $fillable = [
        'deliveryOrderId', 'productId',
        'productName', 'sku', 'qty', 'unitPrice', 'subtotal', 'weight', 'note',
    ];

    public function deliveryOrder()
    {
        return $this->belongsTo(DeliveryOrder::class, 'deliveryOrderId');
    }
}
