<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerMaster extends Model
{
    protected $table = "partnerMasters";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['name', 'status', 'userId', 'userUpdateId'];
}
