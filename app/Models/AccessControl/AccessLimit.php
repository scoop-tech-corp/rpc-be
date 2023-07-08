<?php

namespace App\Models\AccessControl;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessLimit extends Model
{
    use HasFactory;

    protected $table = "accessLimit";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'timeLimit', 'startDuration', 'created_at', 'updated_at'
    ];
}
