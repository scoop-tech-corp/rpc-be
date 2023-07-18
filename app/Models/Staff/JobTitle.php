<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobTitle extends Model
{
    use HasFactory;

    protected $table = "jobTitle";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'jobName', 'isActive', 'created_at', 'updated_at'
    ];
    
}
