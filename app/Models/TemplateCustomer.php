<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemplateCustomer extends Model
{
    protected $table = "templateCustomers";

    protected $dates = ['created_at', 'deletedAt', 'lastChange'];

    protected $guarded = ['id'];

    protected $fillable = [
        'fileName',
        'fileType',
        'lastChange',
        'userId',
        'userUpdateId'
    ];
}
