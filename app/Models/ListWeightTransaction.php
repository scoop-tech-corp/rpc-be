<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListWeightTransaction extends Model
{
    protected $table = "listWeightTransactions";

    protected $dates = ['created_at', 'DeletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'name',
        'userId',
        'userUpdateId'
    ];
}
