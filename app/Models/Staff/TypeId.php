<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeId extends Model
{
    use HasFactory;

    protected $table = "typeId";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'typeName', 'isActive', 'created_at', 'updated_at'
    ];
}
