<?php

namespace App\Models\PushNotification;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class PushNotification extends Model
{
    use HasFactory;

    protected $table = "pushNotifications";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'usersId', 'message', 'type', 'created_at', 'updated_at'
    ];
}
