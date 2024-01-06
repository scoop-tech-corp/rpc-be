<?php

namespace App\Models\Location;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationMessenger extends Model
{
    use HasFactory;

    protected $primaryKey = 'codeLocation';

    protected $table = "location_messenger";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'codeLocation', 'messengerNumber', 'type', 'usage', 'isDeleted', 'created_at', 'updated_at'
    ];
}
