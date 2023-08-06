<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessControlScheduleMaster extends Model
{

    use HasFactory;
    protected $table = "accessControlSchedulesMaster";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'locationId',
        'usersId',
        'createdBy',
        'isDeleted',
        'created_at',
        'updated_at'
    ];
}
