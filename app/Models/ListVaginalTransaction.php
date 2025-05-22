<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListVaginalTransaction extends Model
{
    protected $table = "listVaginalTransactions";

    protected $dates = ['created_at', 'DeletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'name',
        'userId',
        'userUpdateId'
    ];
}
