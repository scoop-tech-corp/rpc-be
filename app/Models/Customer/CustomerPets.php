<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerPets extends Model
{
    use HasFactory;

    protected $table = "customerPets";

    protected $dates = ['dateOfBirth', 'created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'customerId',
        'petName',
        'petCategoryId',
        'races',
        'condition',
        'color',
        'petGender',
        'isSteril',
        'petMonth',
        'petYear',
        'dateOfBirth',
        'isDeleted',
        'deletedBy',
        'deletedAt',
        'created_at',
        'createdBy',
        'userUpdateId',
        'updated_at',
    ];
}
