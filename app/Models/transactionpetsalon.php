<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class transactionpetsalon extends Model
{
    protected $table = "transaction_pet_salons";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'registrationNo',
        'petCheckRegistrationNo',
        'status',
        'isNewCustomer',
        'isNewPet',
        'locationId',
        'customerId',
        'petId',
        'registrant',
        'startDate',
        'endDate',
        'doctorId',
        'note',
        'userId',
        'userUpdateId'
    ];
}
