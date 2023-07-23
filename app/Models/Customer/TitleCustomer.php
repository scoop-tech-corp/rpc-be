<?php

namespace App\Models\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TitleCustomer extends Model
{
    use HasFactory;

    protected $table = "titleCustomer";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'titleName',
        'isActive',
        'created_at',
        'updated_at'
    ];
}
