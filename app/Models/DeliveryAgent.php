<?php

namespace App\Models;

use App\Models\User;
use App\Models\Location\Location;
use Illuminate\Database\Eloquent\Model;

class DeliveryAgent extends Model
{
    protected $table = 'deliveryAgents';

    protected $guarded = ['id'];

    protected $fillable = [
        'locationId', 'name', 'phone', 'identityNumber',
        'vehicleType', 'vehiclePlate', 'isActive', 'note',
        'isDeleted', 'userId', 'userUpdateId', 'deletedBy', 'deletedAt',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class, 'locationId');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function deliveryOrders()
    {
        return $this->hasMany(DeliveryOrder::class, 'agentId');
    }
}
