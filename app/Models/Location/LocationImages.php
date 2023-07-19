<?php

namespace App\Models\Location;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationImages extends Model
{
    use HasFactory;

    protected $primaryKey = 'codeLocation';

    protected $table = "location_images";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;
    
    protected $fillable = [
        'codeLocation', 'labelName', 'realImageName', 'imageName', 'imagePath', 'isDeleted', 'created_at', 'updated_at'
    ];
}
