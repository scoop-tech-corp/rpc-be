<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerSupportRequest extends Model
{
    use HasFactory;

    protected $table = 'customer_support_requests';

    protected $dates = ['created_at', 'updated_at', 'deletedAt', 'resolvedAt'];

    public $timestamps = true;

    protected $guarded = ['id'];

    protected $fillable = [
        'customerId',
        'locationId',
        'subject',
        'message',
        'status',
        'handledBy',
        'resolvedAt',
        'isDeleted',
        'deletedBy',
        'deletedAt',
        'created_at',
        'updated_at',
    ];
}
