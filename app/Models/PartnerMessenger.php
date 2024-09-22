<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerMessenger extends Model
{

    protected $table = "partnerMessengers";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['partnerMasterId','messengerName','typeId','usageId', 'userId', 'userUpdateId'];
}
