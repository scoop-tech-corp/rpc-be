<?php

namespace App\Models;

use App\Models\User;
use App\Models\Location\Location;
use Illuminate\Database\Eloquent\Model;

class LoanProduct extends Model
{
    protected $table = 'loanProducts';

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'loanNumber', 'staffId', 'locationId',
        'eventName', 'eventDate', 'eventAddress',
        'loanDate', 'returnDeadline', 'returnDate',
        'status', 'approvedBy', 'approvedAt', 'rejectedReason',
        'totalItems', 'totalLoanedQty', 'totalSoldQty', 'totalReturnedQty', 'totalRevenue',
        'note', 'returnNote',
        'isDeleted', 'userId', 'userUpdateId', 'deletedBy', 'deletedAt',
    ];

    public function staff()
    {
        return $this->belongsTo(User::class, 'staffId');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'locationId');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approvedBy');
    }

    public function details()
    {
        return $this->hasMany(LoanProductDetail::class, 'loanProductId');
    }

    public function logs()
    {
        return $this->hasMany(LoanProductLog::class, 'loanProductId');
    }
}
