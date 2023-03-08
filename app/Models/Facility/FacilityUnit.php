<?php

namespace App\Models\Facility;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacilityUnit extends Model
{
    use HasFactory;

    protected $table = "facility_unit";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    protected $fillable = [
        'locationId', 'unitName', 'status', 'capacity', 'amount','notes','isDeleted','created_at','updated_at'
    ];

}
