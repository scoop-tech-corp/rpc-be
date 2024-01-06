<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCategories extends Model
{
    use HasFactory;
    protected $table = "serviceCategory";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['categoryName','userId', 'userUpdateId'];

}
