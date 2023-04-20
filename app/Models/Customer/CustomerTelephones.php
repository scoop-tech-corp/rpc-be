<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerTelephones extends Model
{
    use HasFactory;

    protected $table = "customerTelephones";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'customerId',
        'phoneNumber',
        'type',
        'usage',
        'isDeleted',
        'created_at',
        'updated_at',
    ];
}
