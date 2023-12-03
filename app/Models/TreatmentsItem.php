<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TreatmentsItem extends Model
{
    use HasFactory;
    protected $table = 'treatmentsItems';
    protected $dates = ['created_at', 'deletedAt'];
    public $fillable = [
        'treatments_id',
        'frequency_id',
        'duration',
        'quantity',
        'service_id',
        'notes',
        'start',
        'task_id',
        'userId',
        'isDeleted',
        'userUpdateId',
        'deletedBy',
        'deletedAt',
        'product_name',
        'product_type'
    ];
}
