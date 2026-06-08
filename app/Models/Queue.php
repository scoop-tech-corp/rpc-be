<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{
    protected $table = 'queues';

    protected $guarded = ['id'];

    protected $fillable = [
        'queueNumber',
        'serviceType',
        'locationId',
        'customerId',
        'petId',
        'doctorId',
        'bookingId',
        'chiefComplaint',
        'status',
        'queueDate',
        'calledAt',
        'startServiceAt',
        'endServiceAt',
        'createdBy',
        'updatedBy',
        'isDeleted',
    ];
}
