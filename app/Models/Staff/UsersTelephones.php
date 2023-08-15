<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsersTelephones extends Model
{
    use HasFactory;

    protected $table = "usersTelephones";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'usersId', 'phoneNumber', 'type', 'usage', 'isDeleted', 'created_at', 'updated_at'
    ];
}
