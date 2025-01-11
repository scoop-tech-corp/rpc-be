<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $table = "customer";

    protected $dates = ['joinDate', 'birthDate', 'created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'memberNo',
        'firstName',
        'middleName',
        'lastName',
        'nickName',
        'gender',
        'titleCustomerId',
        'customerGroupId',
        'locationId',
        'notes',
        'joinDate',
        'typeId',
        'numberId',
        'occupationId',
        'birthDate',
        'referenceCustomerId',
        'isReminderBooking',
        'isReminderPayment',
        'isDeleted',
        'deletedBy',
        'deletedAt',
        'createdBy',
        'userUpdateId',
        'created_at',
        'updated_at'
    ];
}
