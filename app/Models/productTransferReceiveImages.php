<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class productTransferReceiveImages extends Model
{
    protected $table = "productTransferReceiveImages";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'productTransferDetailId',
        'realImageName',
        'imagePath',
        'labelName',
        'userId',
        'userUpdateId'
    ];
}
