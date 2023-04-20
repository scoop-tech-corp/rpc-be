<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerEmails extends Model
{
    use HasFactory;

    protected $table = "customerEmails";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'customerId',
        'username',
        'usage',
        'isDeleted',
        'created_at',
        'updated_at',
    ];
}

