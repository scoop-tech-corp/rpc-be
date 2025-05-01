<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionPetShopDetail extends Model
{
    use HasFactory;

    protected $table = "transactionpetshopdetail";
    protected $fillable = [
        'transactionpetshopId',
        'productId',
        'quantity',
        'price',
        'note',
        'promoId',
        'userId'
    ];

    public function transaction()
    {
        return $this->belongsTo(TransactionPetShop::class, 'transactionPetShopId');
    }
}
