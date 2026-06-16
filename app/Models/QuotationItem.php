<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationItem extends Model
{
    protected $table = 'quotationItems';

    protected $fillable = [
        'quotationId',
        'itemType',
        'serviceId',
        'productId',
        'itemName',
        'quantity',
        'unitPrice',
        'totalPrice',
        'notes',
    ];
}
