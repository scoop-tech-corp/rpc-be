<?php

namespace App\Models\Location;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $table = "location";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    protected $fillable = [
        'locationId', 'introduction', 'description', 'isDeleted','created_at','updated_at'
    ];
}
