<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class require_salary extends Model
{
    protected $table = "require_salaries";

    protected $dates = ['created_at', 'DeletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'jobId',
        'userId',
        'userUpdateId'
    ];
}
