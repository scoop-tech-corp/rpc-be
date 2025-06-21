<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class staffcontract extends Model
{
    protected $table = "staffcontracts";

    protected $dates = ['created_at', 'DeletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'staffId',
        'startDate',
        'endDate',
        'userId',
        'userUpdateId'
    ];
}
