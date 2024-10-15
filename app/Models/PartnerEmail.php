<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerEmail extends Model
{
    protected $table = "partnerEmails";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['partnerMasterId', 'email', 'usageId', 'userId', 'userUpdateId'];
}
