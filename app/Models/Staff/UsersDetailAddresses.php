<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsersDetailAddresses extends Model
{
    use HasFactory;

    protected $table = "usersDetailAddresses";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'usersid', 'addressName', 'additionalInfo', 'provinceCode', 'cityCode', 'postalCode', 'country', 'isPrimary', 'isDeleted', 'created_at', 'updated_at'
    ];
}
