<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Treatment extends Model
{
    use HasFactory;
    protected $table = 'treatments';
    protected $dates = ['created_at', 'deletedAt'];
    public $fillable = [
        'name',
        'location_id',
        'diagnose_id',
        'column',
        'status',
        'userId',
        'userUpdateId',
        'deletedBy'
    ];
    // status:
    // ACTIVE = 1;
    // DRAFT = 2;
    // INACTIVE = 3;

}
