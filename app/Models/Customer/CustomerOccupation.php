<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerOccupation extends Model
{
    use HasFactory;

    protected $table = "customerOccupation";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'occupationName',
        'isActive',
        'created_at',
        'updated_at'
    ];
}
