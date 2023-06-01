<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{

    use HasFactory;
    
    protected $primaryKey = 'codeLocation';

    protected $table = "location";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'codeLocation', 'locationName', 'status', 'description', 'isDeleted', 'created_at', 'updated_at'
    ];

    public function postsLocationDetailAddress()
    {
        return $this->hasMany(LocationDetailAddress::class, 'codeLocation');
    }

    public function postsLocationEmail()
    {
        return $this->hasMany(LocationEmail::class, 'codeLocation');
    }

    public function postsLocationImages()
    {
        return $this->hasMany(LocationImages::class, 'codeLocation');
    }

    public function postsLocationMessenger()
    {
        return $this->hasMany(LocationMessenger::class, 'codeLocation');
    }

    public function postsLocationOperational()
    {
        return $this->hasMany(LocationOperational::class, 'codeLocation');
    }

    public function postsLocationTelephone()
    {
        return $this->hasMany(LocationTelephone::class, 'codeLocation');
    }
}
