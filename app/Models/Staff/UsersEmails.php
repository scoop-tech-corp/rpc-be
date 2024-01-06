<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UsersEmails extends Model
{
    use HasFactory;

    protected $table = "usersEmails";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'usersId', 'email', 'email_verified_at', 'usage', 'isDeleted', 'created_at', 'updated_at'
    ];
}
