<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCategoryList extends Model
{
    use HasFactory;
    protected $table = "servicesCategoryList";

    protected $fillable = [
        'service_id',
        'category_id',
        'userId',
        'isDeleted',
        'userUpdateId',
        'deletedBy',
        'deletedAt',
    ];

}
