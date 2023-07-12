<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class productTransferSentImages extends Model
{
    protected $table = "productTransferSentImages";

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
