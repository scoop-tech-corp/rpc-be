<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    protected $table = "products";

    protected $dates = ['created_at', 'DeletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'category','fullName', 'simpleName', 'sku',
        'productBrandId', 'productSupplierId', 'status', 'expiredDate', 'pricingStatus',
        'costPrice', 'marketPrice', 'price',
        'isShipped', 'weight', 'length', 'width',
        'height', 'introduction', 'description', 'isCustomerPurchase', 'isCustomerPurchaseOnline',
        'isCustomerPurchaseOutStock', 'isStockLevelCheck', 'isNonChargeable',
        'isOfficeApproval', 'isAdminApproval', 'userId', 'userUpdateId'
    ];
}
