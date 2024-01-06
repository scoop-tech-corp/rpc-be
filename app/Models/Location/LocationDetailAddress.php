<?php

namespace App\Models\Location;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationDetailAddress extends Model
{
    use HasFactory;

    protected $primaryKey = 'codeLocation';

    protected $table = "location_detail_address";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;
    
    protected $fillable = [
        'codeLocation', 
        'addressName', 
        'additionalInfo', 
        'provinceCode', 
        'cityCode',
        'postalCode',
        'country',
        'isPrimary',
        'isDeleted',
        'created_at',
        'updated_at',
    ];

}
