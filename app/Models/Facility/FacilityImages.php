<?php

namespace App\Models\Facility;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacilityImages extends Model
{
    use HasFactory;

    protected $table = "facility_images";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    protected $fillable = [
        'locationId', 'labelName', 'realImageName', 'imageName', 'imagePath', 'isDeleted', 'created_at', 'updated_at'
    ];
}
