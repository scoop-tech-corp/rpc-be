<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    protected $table = 'quotations';

    protected $fillable = [
        'quotationNo',
        'status',
        'customerId',
        'petId',
        'locationId',
        'typeOfService',
        'validUntil',
        'notes',
        'subtotalAmount',
        'discountAmount',
        'finalAmount',
        'convertedTransactionId',
        'convertedTransactionType',
        'isDeleted',
        'deletedBy',
        'deletedAt',
        'userId',
        'userUpdateId',
    ];

    public function items()
    {
        return $this->hasMany(QuotationItem::class, 'quotationId');
    }

    public function logs()
    {
        return $this->hasMany(QuotationLog::class, 'quotationId');
    }
}
