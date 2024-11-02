<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportCustomer extends Model
{
    protected $table = "importCustomers";

    protected $dates = ['created_at', 'deletedAt', 'lastChange'];

    protected $guarded = ['id'];

    protected $fillable = [
        'fileName',
        'totalData',
        'userId',
        'userUpdateId'
    ];
}
