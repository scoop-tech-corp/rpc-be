<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerGroups extends Model
{
    protected $table = "customerGroups";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = ['customerGroup', 'userId', 'userUpdateId'];
}
