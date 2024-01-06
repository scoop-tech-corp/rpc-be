<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductTransferLog extends Model
{
    protected $table = "productTransferLogs";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['productTransferId', 'event', 'details', 'userId', 'userUpdateId'];
}
