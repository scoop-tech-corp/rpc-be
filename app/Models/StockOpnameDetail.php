<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOpnameDetail extends Model
{
    protected $table = "stock_opname_details";

    protected $dates = ['created_at', 'DeletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'stockOpnameId',
        'productId',
        'stockSystem',
        'stockPhysical',
        'difference',
        'status',
        'note',
        'inputedBy',
        'inputedAt',
        'imagePath',
        'userId',
        'userUpdateId'
    ];
}
