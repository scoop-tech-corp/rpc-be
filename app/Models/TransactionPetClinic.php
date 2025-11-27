<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionPetClinic extends Model
{
    protected $table = "transactionPetClinics";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'registrationNo',
        'nota_number',
        'status',
        'isNewCustomer',
        'isNewPet',
        'locationId',
        'customerId',
        'petId',
        'registrant',
        'typeOfCare',
        'startDate',
        'endDate',
        'doctorId',
        'note',
        'proofOfPayment',
        'originalName',
        'proofRandomName',
        'userId',
        'userUpdateId'
    ];
}
