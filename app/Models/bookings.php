<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class bookings extends Model
{
    protected $table = "bookings";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'locationId',
        'doctorId',
        'customerId',
        'petId',
        'serviceType',
        'bookingTime',
        'isCancelled',
        'status',
        'cancellationReason',
        'canceledByName',
        'cancellationDate',
        'isAccepted',
        'acceptedByName',
        'acceptedDate',
        'isRejected',
        'rejectionReason',
        'rejectedByName',
        'rejectionDate',
        'realImageName',
        'imagePath',
        'userId',
        'userUpdateId'
    ];
}
