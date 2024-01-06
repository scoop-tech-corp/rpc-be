<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataStaticCustomers extends Model
{
    use HasFactory;

    protected $table = "dataStaticCustomer";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'value', 'name', 'isDeleted', 'created_at', 'updated_at'
    ];
}
