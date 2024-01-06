<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeIdCustomer extends Model
{
    use HasFactory;

    protected $table = "typeIdCustomer";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'typeName',
        'isActive',
        'created_at',
        'updated_at',

    ];
}
