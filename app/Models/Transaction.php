<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = "transactions";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'registrationNo',
        'status',
        'isNewCustomer',
        'isNewPet',
        'locationId',
        'customerId',
        'petId',
        'registrant',
        'serviceCategory',
        'startDate',
        'endDate',
        'doctorId',
        'note',
        'userId',
        'userUpdateId'
    ];
}
