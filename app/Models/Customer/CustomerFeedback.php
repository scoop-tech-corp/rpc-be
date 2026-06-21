<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerFeedback extends Model
{
    use HasFactory;

    protected $table = 'customer_feedbacks';

    protected $dates = ['created_at', 'updated_at', 'deletedAt'];

    public $timestamps = true;

    protected $guarded = ['id'];

    protected $fillable = [
        'customerId',
        'locationId',
        'transactionId',
        'transactionType',
        'rating',
        'message',
        'isDeleted',
        'deletedBy',
        'deletedAt',
        'created_at',
        'updated_at',
    ];
}
