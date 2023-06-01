<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SourceCustomer extends Model
{
    use HasFactory;

    protected $table = "sourceCustomer";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'sourceName',
        'isActive',
        'created_at',
        'updated_at',

    ];
}
