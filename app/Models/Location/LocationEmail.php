<?php

namespace App\Models\Location;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationEmail extends Model
{
    use HasFactory;

    protected $primaryKey = 'codeLocation';

    protected $table = "location_email";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;
    
    protected $fillable = [
        'codeLocation', 'username', 'usage', 'isDeleted', 'created_at', 'updated_at'
    ];
}
