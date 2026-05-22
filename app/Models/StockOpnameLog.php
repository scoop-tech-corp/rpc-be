<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOpnameLog extends Model
{
    protected $table = 'stock_opname_logs';

    protected $fillable = ['stockOpnameId', 'event', 'details', 'userId'];
}
