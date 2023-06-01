<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferenceCustomer extends Model
{
    use HasFactory;

    protected $table = "referenceCustomer";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'referenceName',
        'isActive',
        'created_at',
        'updated_at'
    ];
}
