<?php

namespace App\Models\Facility;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    use HasFactory;

    protected $table = "facility";

    protected $dates = [ 'created_at', 'updated_at'];

    protected $guarded = ['id'];

    protected $fillable = [
        'locationId', 'introduction', 'description', 'isDeleted', 'created_at','updated_at'
    ];

    public function postsFacilityUnit()
    {
        return $this->hasMany(FacilityUnit::class);
    }

    public function postsFacilityImages()
    {
        return $this->hasMany(FacilityImages::class, 'locationId');
    }

}
