<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $table = "leaveRequest";

    protected $dates = ['fromDate', 'toDate', 'created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [

        'usersId', 'requesterName', 'jobTitle', 'locationId','locationName', 'leaveType','fromDate', 'toDate', 'duration', 'workingDays',
        'status', 'remark', 'approveOrRejectedBy', 'approveOrRejectedDate', 'rejectedReason','created_at', 'updated_at'
    ];
}
