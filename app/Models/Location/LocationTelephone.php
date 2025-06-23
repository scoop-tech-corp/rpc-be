<?php

namespace App\Models\Location;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationTelephone extends Model
{
    use HasFactory;

    protected $primaryKey = 'codeLocation';

    protected $table = "location_telephone";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'codeLocation',
        'phoneNumber',
        'type',
        'usage',
        'isDeleted',
        'created_at',
        'updated_at'
    ];

    public function location()
    {
        return $this->belongsTo(Location::class, 'codeLocation', 'codeLocation');
    }
}
