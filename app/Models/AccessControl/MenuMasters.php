<?php

namespace App\Models\AccessControl;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuMasters extends Model
{
    use HasFactory;

    protected $table = "menuMaster";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'menuMaster', 'isDeleted', 'created_at', 'updated_at'
    ];
}
