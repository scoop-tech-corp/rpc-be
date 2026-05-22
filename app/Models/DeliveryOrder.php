<?php

namespace App\Models;

use App\Models\User;
use App\Models\Location\Location;
use Illuminate\Database\Eloquent\Model;

class DeliveryOrder extends Model
{
    protected $table = 'deliveryOrders';

    protected $dates = ['created_at', 'scheduledAt', 'pickedUpAt', 'deliveredAt', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'deliveryNumber', 'locationId', 'agentId',
        'customerId', 'customerName', 'customerPhone', 'deliveryAddress',
        'deliveryDate', 'deliveryTime', 'scheduledAt',
        'pickedUpAt', 'deliveredAt',
        'status', 'failedReason', 'cancelledReason', 'proofImageUrl',
        'orderId', 'totalItems', 'totalWeight', 'totalAmount', 'note',
        'isDeleted', 'userId', 'userUpdateId', 'deletedBy', 'deletedAt',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class, 'locationId');
    }

    public function agent()
    {
        return $this->belongsTo(DeliveryAgent::class, 'agentId');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function details()
    {
        return $this->hasMany(DeliveryOrderDetail::class, 'deliveryOrderId');
    }

    public function logs()
    {
        return $this->hasMany(DeliveryOrderLog::class, 'deliveryOrderId')->orderBy('created_at', 'asc');
    }
}
