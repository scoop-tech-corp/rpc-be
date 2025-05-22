<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialDataTransaction extends Model
{
    protected $table = "materialDataTransactions";

    protected $dates = ['created_at', 'DeletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'category', 'name', 'userId', 'userUpdateId'
    ];
}
