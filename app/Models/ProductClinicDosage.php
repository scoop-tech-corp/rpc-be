<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductClinicDosage extends Model
{
    protected $table = "productClinicDosages";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = ['productClinicId', 'from', 'to', 'dosage', 'unit', 'userId', 'userUpdateId'];
}
