<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DeliveryOrderLog extends Model
{
    protected $table = 'deliveryOrderLogs';

    protected $guarded = ['id'];

    protected $fillable = [
        'deliveryOrderId', 'action', 'description', 'userId',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }
}
