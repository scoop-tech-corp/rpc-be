<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayPeriod extends Model
{
    use HasFactory;

    protected $table = "payPeriod";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'periodName', 'isActive', 'created_at', 'updated_at'

    ];
}
