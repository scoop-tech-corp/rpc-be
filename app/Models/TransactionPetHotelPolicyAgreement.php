<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionPetHotelPolicyAgreement extends Model
{
    protected $table = 'transaction_pet_hotel_policy_agreements';

    protected $fillable = [
        'transactionId',
        'contractTemplateId',
        'contractTitle',
        'contractVersion',
        'signatureData',
        'signerName',
        'signedAt',
        'userId',
    ];
}
