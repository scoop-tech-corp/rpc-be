<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $table = "leaverequest";

    protected $dates = ['fromDate', 'toDate', 'created_at', 'updated_at'];

    protected $guarded = ['id'];

    protected $fillable = [
        'usersId', 'requesterName', 'jobtitle', 'locationId', 'leaveType','fromDate', 'toDate', 'duration', 'workingdays',
        'status', 'remark', 'approveOrRejectedBy', 'approveOrRejectedDate', 'rejectedReason','created_at', 'updated_at'
    ];
}
