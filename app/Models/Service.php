<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\ServiceCategoryList;
use App\Models\ServiceFacility;
use App\Models\ServiceStaff;
use App\Models\ServiceProductRequired;
use App\Models\ServiceLocation;
use App\Models\ServicePrice;
use App\Models\ServiceImage;

class Service extends Model
{
    use HasFactory;
    protected $table = 'services';
    public $fillable = [
        'fullName',
        'simpleName',
        'type',
        'color',
        'status',
        'policy',
        'surcharges',
        'staffPerBooking',
        'introduction',
        'description',
        'optionPolicy1',
        'optionPolicy2',
        'optionPolicy3',
        'userId',
        'isDeleted',
        'userUpdateId',
        'deletedBy',
        'deletedAt',
    ];

    public function categoryList()
    {
        return $this
        ->hasMany(ServiceCategoryList::class, 'service_id', 'id')
        ->where('servicesCategoryList.isDeleted', 0)
        ->join('serviceCategory', 'serviceCategory.id', '=', 'servicesCategoryList.category_id')
        ->select('servicesCategoryList.id', 'servicesCategoryList.service_id', 'servicesCategoryList.category_id','serviceCategory.categoryName as categoryName','servicesCategoryList.created_at');
    }
    public function facilityList(){
        return $this
        ->hasMany(ServiceFacility::class, 'service_id', 'id')
        ->where('servicesFacility.isDeleted', 0)
        ->join('facility_unit', 'facility_unit.id', '=', 'servicesFacility.facility_id')
        ->select('servicesFacility.facility_id', 'servicesFacility.id', 'servicesFacility.service_id', 'facility_unit.unitName as unitName','servicesFacility.created_at');
    }
    public function staffList(){
        return $this
        ->hasMany(ServiceStaff::class, 'service_id', 'id')
        ->where('servicesStaff.isDeleted', 0);
    }
    public function productRequiredList(){
        return $this
        ->hasMany(ServiceProductRequired::class, 'service_id', 'id')
        ->where('servicesProductRequired.isDeleted', 0);
    }
    public function locationList(){
        return $this
        ->hasMany(ServiceLocation::class, 'service_id', 'id')
        ->where('servicesLocation.isDeleted', 0)
        ->join('location', 'location.id', '=', 'servicesLocation.location_id')
        ->select('servicesLocation.id', 'location.id as locationId','servicesLocation.service_id', 'location.locationName as locationName');
    }
    public function priceList(){
        return $this
        ->hasMany(ServicePrice::class, 'service_id', 'id')
        ->where('servicesPrice.isDeleted', 0)
        ->with(['location', 'customerGroup']);
    }
    public function imageList(){
        return $this
        ->hasMany(ServiceImage::class, 'service_id', 'id')
        ->where('servicesImages.isDeleted', 0);
    }

    public function followupList(){
        return $this
        ->belongsToMany(Service::class, 'servicesFollowup', 'service_id', 'followup_id')
        ->where('servicesFollowup.isDeleted', 0)
        ->select('servicesFollowup.id','services.id as service_id', 'services.fullName', 'services.created_at');
    }
}
