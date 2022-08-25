<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class exceltesting extends Model
{
    use HasFactory;
    protected $table = "exceltesting";
    protected $fillable = ['a', 'b', 'c', 'd', 'e'];
}
