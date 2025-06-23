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

    public $timestamps = true;

    protected $fillable = [
        'codeLocation',
        'locationName',
        'status',
        'description',
        'isDeleted',
        'created_at',
        'updated_at',
    ];

    public function telephones()
    {
        // Relasi berdasarkan codeLocation
        return $this->hasMany(LocationTelephone::class, 'codeLocation', 'codeLocation');
    }
}
