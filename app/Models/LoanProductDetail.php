<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoanProductDetail extends Model
{
    protected $table = 'loanProductDetails';

    protected $guarded = ['id'];

    protected $fillable = [
        'loanProductId', 'productType', 'productId',
        'productName', 'sku',
        'loanedQty', 'costPrice', 'suggestedPrice',
        'soldQty', 'actualSellingPrice', 'returnedQty', 'revenue',
        'itemNote', 'returnStatus',
    ];

    public function loan()
    {
        return $this->belongsTo(LoanProduct::class, 'loanProductId');
    }
}
