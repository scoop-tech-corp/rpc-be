<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataStaticStaff extends Model
{
    use HasFactory;

    protected $table = "dataStaticStaff";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'value', 'name', 'isDeleted', 'created_at', 'updated_at'
    ];
    
}
