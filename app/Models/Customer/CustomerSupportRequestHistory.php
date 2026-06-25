<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Model;

class CustomerSupportRequestHistory extends Model
{
    protected $table    = 'customer_support_request_histories';
    protected $fillable = [
        'supportRequestId',
        'fromStatus',
        'toStatus',
        'changedBy',
        'notes',
    ];
}
