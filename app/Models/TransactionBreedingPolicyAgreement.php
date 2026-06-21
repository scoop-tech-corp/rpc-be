<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionBreedingPolicyAgreement extends Model
{
    protected $table = 'transaction_breeding_policy_agreements';

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
