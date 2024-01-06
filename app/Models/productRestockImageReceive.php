<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class productRestockImageReceive extends Model
{
    protected $table = "productRestockImageReceives";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'productRestockDetailId',
        'realImageName',
        'imagePath',
        'userId',
        'userUpdateId'
    ];
}
