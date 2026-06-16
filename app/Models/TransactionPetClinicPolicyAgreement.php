<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionPetClinicPolicyAgreement extends Model
{
    protected $table = 'transactionPetClinicPolicyAgreements';
    protected $guarded = ['id'];
    protected $fillable = [
        'transactionId', 'contractTemplateId', 'contractTitle',
        'contractVersion', 'signatureData', 'signerName', 'signedAt', 'userId',
    ];
    protected $dates = ['signedAt', 'created_at', 'updated_at'];
}
