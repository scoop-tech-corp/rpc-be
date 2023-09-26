<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\location;
use App\Models\CustomerGroups;

class ServicePrice extends Model
{
    use HasFactory;
    protected $table = "servicesPrice";

    public function location(){
        return $this->hasOne(location::class, 'id', 'location_id')->select('id', 'locationName as label');
    }
    public function customerGroup(){
        return $this->hasOne(CustomerGroups::class, 'id', 'customer_group_id')->select('id', 'customerGroup as label');
    }


}
