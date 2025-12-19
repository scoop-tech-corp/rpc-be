<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOpnameMaster extends Model
{
    protected $table = "stock_opname_masters";

    protected $dates = ['created_at', 'DeletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'stockOpnameNumber',
        'title',
        'startTime',
        'locationId',
        'status',
        'reason',
        'userId',
        'userUpdateId'
    ];
}
