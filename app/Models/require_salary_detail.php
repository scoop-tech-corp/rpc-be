<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class require_salary_detail extends Model
{
    protected $table = "require_salary_details";

    protected $dates = ['created_at', 'DeletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'requireSallaryId',
        'typeId',
        'userId',
        'userUpdateId'
    ];
}
