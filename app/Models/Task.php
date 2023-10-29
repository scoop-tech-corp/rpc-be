<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;
    protected $table = 'task';
    protected $dates = ['created_at', 'deletedAt'];
    public $fillable = [
        'name',
        'userId',
        'userUpdateId',
        'deletedBy'
    ];}
