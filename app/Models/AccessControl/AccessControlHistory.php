<?php

namespace App\Models\AccessControl;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessControlHistory extends Model
{
    use HasFactory;

    protected $table = "accessControlHistory";

    protected $dates = ['created_at', 'updated_at'];

    protected $guarded = ['id'];

    public $timestamps = true;

    protected $fillable = [
        'menuName', 'remark', 'updatedBy', 'created_at', 'updated_at'
    ];

}
