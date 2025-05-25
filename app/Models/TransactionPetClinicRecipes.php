<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionPetClinicRecipes extends Model
{
    protected $table = "transaction_pet_clinic_recipes";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'transactionPetClinicId',
        'productId',
        'dosage',
        'unit',
        'frequency',
        'giveMedicine',
        'userId',
        'userUpdateId'
    ];
}
