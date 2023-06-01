<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerReminder extends Model
{
    use HasFactory;

    protected $table = "customerReminders";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'customerId',
        'sourceId',
        'unit',
        'timing',
        'status',
        'type',
        'isDeleted',
        'deletedBy',
        'deletedAt',
        'created_at',
        'updated_at',
    ];
}
