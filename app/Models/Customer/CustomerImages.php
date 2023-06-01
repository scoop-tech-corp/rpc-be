<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerImages extends Model
{
    use HasFactory;

    protected $table = "customerImages";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'customerId',
        'labelName',
        'realImageName',
        'imageName',
        'imagePath',
        'isDeleted',
        'created_at',
        'updated_at',
    ];
}
