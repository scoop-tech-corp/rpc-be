<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $table = "location";

    protected $guarded = ['id'];

    protected $fillable = ['codeLocation','locationName', 'isBranch', 'status'];


    // public function location_operation_hours_detail()
    // {
    //     return $this->hasMany('Location_Operational_Hours_Detail');
    // }

}
