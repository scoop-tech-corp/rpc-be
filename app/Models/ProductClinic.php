<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductClinic extends Model
{
    protected $table = "productClinics";

    protected $dates = ['created_at', 'DeletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'fullName', 'simpleName', 'sku',
        'productBrandId', 'productSupplierId', 'status', 'expiredDate', 'pricingStatus',
        'costPrice', 'marketPrice', 'price',
        'isShipped', 'weight', 'length', 'width',
        'height', 'introduction', 'description', 'isCustomerPurchase', 'isCustomerPurchaseOnline',
        'isCustomerPurchaseOutStock', 'isStockLevelCheck', 'isNonChargeable',
        'isOfficeApproval', 'isAdminApproval', 'userId', 'userUpdateId'
    ];
}
