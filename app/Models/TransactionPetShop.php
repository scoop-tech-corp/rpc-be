<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionPetShop extends Model
{
    use HasFactory;

    protected $table = "transactionpetshop";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'registrationNo',
        'locationId',
        'customerId',
        'paymentMethod',
        'originalName',
        'proofRandomName',
        'userId'
    ];
}
