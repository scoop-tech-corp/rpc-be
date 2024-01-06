<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsersMessengers extends Model
{
    use HasFactory;

    protected $table = "usersMessengers";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'usersId', 'messengerNumber', 'type', 'usage', 'isDeleted', 'created_at', 'updated_at'
    ];
}
