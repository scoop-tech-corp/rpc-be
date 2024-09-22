<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerPhone extends Model
{
    protected $table = "partnerPhones";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['partnerMasterId','phoneNumber','typeId','usageId', 'userId', 'userUpdateId'];
}
