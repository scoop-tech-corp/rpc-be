<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerMessengers extends Model
{
    use HasFactory;

    protected $table = "customerMessengers";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'customerId',
        'messengerNumber',
        'type',
        'usage',
        'isDeleted',
        'created_at',
        'updated_at',
    ];
}

