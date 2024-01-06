<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diagnose extends Model
{
    use HasFactory;
    protected $table = 'diagnose';
    protected $dates = ['created_at', 'deletedAt'];
    public $fillable = [
        'name',
        'userId',
        'userUpdateId',
        'isDeleted',
        'deletedBy'
    ];

}
