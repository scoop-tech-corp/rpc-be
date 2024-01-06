<?php

namespace App\Models\Location;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationOperational extends Model
{
    use HasFactory;

    protected $primaryKey = 'codeLocation';

    protected $table = "location_operational";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;
    
    protected $fillable = [
        'codeLocation', 'dayName', 'fromTime', 'toTime', 'allDay', 'created_at', 'updated_at'

    ];
}
