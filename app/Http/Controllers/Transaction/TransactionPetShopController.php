<?php

namespace App\Http\Controllers\Transaction;

use DB;
use Validator;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\ProductLocations;
use App\Models\Customer\Customer;
use App\Models\TransactionPetShop;
use App\Models\Staff\UsersLocation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Models\TransactionPetShopDetail;

class TransactionPetShopController
{
    // public function index(Request $request)
    // {
    //     $itemPerPage = $request->rowPerPage;
    //     $page = $request->goToPage;

    //     $subDetail = DB::table('transactionpetshopdetail as d')
    //         ->join('transactionpetshop as tp', 'tp.id', '=', 'd.transactionpetshopId')
    //         ->select(
    //             'tp.id as transaction_id',
    //             DB::raw('SUM(d.quantity) as totalItem'),
    //             DB::raw('SUM(CASE WHEN d.promoId IS NOT NULL THEN 1 ELSE 0 END) as totalUsePromo'),
    //             DB::raw('SUM(d.quantity * d.price) as totalAmount')
    //         )
    //         ->groupBy('tp.id');



    //     $data = DB::table('transactionpetshop as tp')
    //         ->join('customer as c', 'tp.customerId', '=', 'c.id')
    //         ->join('location as l', 'tp.locationId', '=', 'l.id')
    //         ->join('customergroups as cg', 'c.customerGroupId', '=', 'cg.id')
    //         ->leftJoinSub($subDetail, 'detail', function ($join) {
    //             $join->on('tp.id', '=', 'detail.transaction_id');
    //         })
    //         ->select(
    //             'tp.id',
    //             'tp.registrationNo',
    //             'tp.locationId',
    //             'tp.customerId',
    //             'cg.customerGroup as customerGroup',
    //             DB::raw('COALESCE(detail.totalItem, 0) as totalItem'),
    //             DB::raw('COALESCE(detail.totalUsePromo, 0) as totalUsePromo'),
    //             DB::raw('COALESCE(detail.totalAmount, 0) as totalAmount'),
    //             'c.nickName as customerName',
    //             'l.locationName'
    //         )
    //         ->where('tp.isDeleted', '=', 0);


    //     $roleId = $request->user()->roleId;

    //     if ($roleId == 1) {
    //         if ($request->locationId) {
    //             $data = $data->whereIn('tp.locationId', $request->locationId);
    //         }
    //     } else {
    //         $locations = UsersLocation::where('usersId', $request->user()->id)->pluck('id')->toArray();
    //         $data = $data->whereIn('tp.locationId', $locations);
    //     }


    //     if ($request->customerGroupId) {
    //         $data = $data->whereIn('c.customerGroupId', $request->customerGroupId);
    //     }

    //     if ($request->serviceCategories) {
    //         $data = $data->whereIn('tp.serviceCategory', $request->serviceCategories);
    //     }


    //     if ($request->search) {
    //         $res = $this->Search($request);
    //         if ($res) {
    //             $data = $data->where(function ($query) use ($res, $request) {
    //                 $query->where($res[0], 'like', '%' . $request->search . '%');
    //                 for ($i = 1; $i < count($res); $i++) {
    //                     $query->orWhere($res[$i], 'like', '%' . $request->search . '%');
    //                 }
    //             });
    //         } else {
    //             return response()->json([
    //                 'totalPagination' => 0,
    //                 'data' => []
    //             ], 200);
    //         }
    //     }

    //     if ($request->orderValue) {
    //         $data = $data->orderBy($request->orderColumn, $request->orderValue);
    //     }

    //     $data = $data->orderBy('tp.updated_at', 'desc');


    //     $offset = ($page - 1) * $itemPerPage;
    //     $count_data = $data->count();
    //     $totalPaging = ceil($count_data / $itemPerPage);

    //     $data = $data->offset($offset)->limit($itemPerPage)->get();

    //     return responseIndex($totalPaging, $data);
    // }

    public function index(Request $request)
    {
        $itemPerPage = $request->rowPerPage;
        $page = $request->goToPage;

        $subDetail = DB::table('transactionpetshopdetail as d')
            ->join('transactionpetshop as tp', 'tp.id', '=', 'd.transactionpetshopId')
            ->select(
                'tp.id as transaction_id',
                DB::raw('SUM(d.quantity) as totalItem'),
                DB::raw('SUM(CASE WHEN d.promoId IS NOT NULL THEN 1 ELSE 0 END) as totalUsePromo'),
                DB::raw('SUM(d.quantity * d.price) as totalAmount')
            )
            ->groupBy('tp.id');

        $data = DB::table('transactionpetshop as tp')
            ->join('customer as c', 'tp.customerId', '=', 'c.id')
            ->join('location as l', 'tp.locationId', '=', 'l.id')
            ->join('customerGroups as cg', 'c.customerGroupId', '=', 'cg.id')
            ->leftJoinSub($subDetail, 'detail', function ($join) {
                $join->on('tp.id', '=', 'detail.transaction_id');
            })
            ->select(
                'tp.id',
                'tp.registrationNo',
                'tp.locationId',
                'tp.customerId',
                'cg.customerGroup as customerGroup',
                DB::raw('COALESCE(detail.totalItem, 0) as totalItem'),
                DB::raw('COALESCE(detail.totalUsePromo, 0) as totalUsePromo'),
                DB::raw('COALESCE(detail.totalAmount, 0) as totalAmount'),
                'c.nickName as customerName',
                'l.locationName'
            )
            ->where('tp.isDeleted', '=', 0);

        $roleId = $request->user()->roleId;

        if ($roleId == 1) {
            if ($request->locationId) {
                $data = $data->whereIn('tp.locationId', $request->locationId);
            }
        } else {
            $locations = UsersLocation::where('usersId', $request->user()->id)->pluck('id')->toArray();
            $data = $data->whereIn('tp.locationId', $locations);
        }

        if ($request->customerGroupId) {
            $data = $data->whereIn('c.customerGroupId', $request->customerGroupId);
        }

        if ($request->serviceCategories) {
            $data = $data->whereIn('tp.serviceCategory', $request->serviceCategories);
        }

        if ($request->search) {
            $res = $this->Search($request);
            if ($res) {
                $data = $data->where(function ($query) use ($res, $request) {
                    $query->where($res[0], 'like', '%' . $request->search . '%');
                    for ($i = 1; $i < count($res); $i++) {
                        $query->orWhere($res[$i], 'like', '%' . $request->search . '%');
                    }
                });
            } else {
                return response()->json([
                    'totalPagination' => 0,
                    'data' => []
                ], 200);
            }
        }

        $allowedColumns = [
            'tp.registrationNo',
            'c.nickName',
            'l.locationName',
            'detail.totalAmount',
            'detail.totalItem',
            'detail.totalUsePromo',
            'tp.updated_at'
        ];

        $orderColumn = in_array($request->orderColumn, $allowedColumns) ? $request->orderColumn : 'tp.updated_at';
        $orderValue = in_array(strtolower($request->orderValue), ['asc', 'desc']) ? $request->orderValue : 'desc';

        $data = $data->orderBy(DB::raw($orderColumn), $orderValue);

        $offset = ($page - 1) * $itemPerPage;
        $count_data = $data->count();
        $totalPaging = ceil($count_data / $itemPerPage);

        $data = $data->offset($offset)->limit($itemPerPage)->get();

        return responseIndex($totalPaging, $data);
    }


    private function Search(Request $request)
    {
        $temp_column = [];


        $data = DB::table('transactionpetshop as tp')
            ->select('tp.registrationNo')
            ->where('tp.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('tp.registrationNo', 'like', '%' . $request->search . '%');
        }

        if ($data->exists()) {
            $temp_column[] = 'tp.registrationNo';
        }


        $data = DB::table('transactionpetshop as tp')
            ->join('customer as c', 'c.id', '=', 'tp.customerId')
            ->select('c.nickName')
            ->where('tp.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('c.nickName', 'like', '%' . $request->search . '%');
        }

        if ($data->exists()) {
            $temp_column[] = 'c.nickName';
        }


        $data = DB::table('transactionpetshop as tp')
            ->join('location as l', 'l.id', '=', 'tp.locationId')
            ->select('l.locationName')
            ->where('tp.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('l.locationName', 'like', '%' . $request->search . '%');
        }

        if ($data->exists()) {
            $temp_column[] = 'l.locationName';
        }

        return $temp_column;
    }

    public function create(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'isNewCustomer' => 'required|boolean',
            'locationId' => 'required|integer',
            'serviceCategory' => 'required|string|in:Pet Clinic,Pet Hotel,Pet Salon,Pet Shop,Pacak',
            'paymentMethod' => 'required|integer',
            'productList' => 'required|array|min:1',
            'productList.*.productId' => 'required|integer',
            'productList.*.quantity' => 'required|integer|min:1',
            'productList.*.price' => 'required|integer|min:0',
            'productList.*.note' => 'nullable|string',
            'productList.*.promoId' => 'nullable|integer',
            'selectedPromos' => 'nullable|array',
            'selectedPromos.freeItems' => 'nullable|array',
            'selectedPromos.discounts' => 'nullable|array',
            'selectedPromos.bundles' => 'nullable|array',
            'selectedPromos.basedSales' => 'nullable|array',
        ]);

        if ($request->isNewCustomer) {
            $validate->after(function ($validator) use ($request) {
                if (empty($request->customerName)) {
                    $validator->errors()->add('customerName', 'Customer name is required for new customer.');
                }
            });
        } else {
            $validate->after(function ($validator) use ($request) {
                if (empty($request->customerId)) {
                    $validator->errors()->add('customerId', 'Customer ID is required for existing customer.');
                }
            });
        }

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        DB::beginTransaction();
        try {
            if ($request->isNewCustomer) {
                $cust = DB::table('customer')->insertGetId([
                    'firstName' => $request->customerName,
                    'locationId' => $request->locationId,
                    'typeId' => 0,
                    'memberNo' => '',
                    'gender' => '',
                    'joinDate' => Carbon::now(),
                    'createdBy' => $request->user()->id,
                    'userUpdateId' => $request->user()->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $cust = DB::table('customer')
                    ->select('id', 'isDeleted')
                    ->where('id', $request->customerId)
                    ->where('isDeleted', 0)
                    ->first();

                if (!$cust) {
                    return responseInvalid(['Customer not found.']);
                }

                $cust = $cust->id;
            }

            $promoResult = null;
            $promoNotes = [];
            $totalDiscount = 0;

            if (!empty($request->selectedPromos['bundles']) || !empty($request->selectedPromos['basedSales'])) {
                $productsForPromo = [];
                foreach ($request->productList as $prod) {
                    $productsForPromo[] = [
                        'productId' => $prod['productId'],
                        'quantity' => $prod['quantity'],
                        'eachPrice' => $prod['price'],
                        'priceOverall' => $prod['quantity'] * $prod['price'],
                        'locationId' => $request->locationId
                    ];
                }

                $promoRequest = new Request();
                $promoRequest->products = json_encode($productsForPromo);
                $promoRequest->freeItems = json_encode([]);
                $promoRequest->discounts = json_encode([]); // abaikan karena sudah dihitung manual
                $promoRequest->bundles = json_encode($request->selectedPromos['bundles'] ?? []);
                $promoRequest->basedSales = json_encode($request->selectedPromos['basedSales'] ?? []);

                $promoResult = $this->transactionDiscount($promoRequest);

                if (!isset($promoResult['purchases'])) {
                    return responseInvalid(['Failed to process promotions.']);
                }

                $totalDiscount += $promoResult['total_discount'] ?? 0;
            }


            $lowStockWarnings = [];
            foreach ($request->productList as $prod) {
                $productLoc = DB::table('productLocations')
                    ->where('locationId', $request->locationId)
                    ->where('productId', $prod['productId'])
                    ->first();

                if (!$productLoc) {
                    return responseInvalid(["Produk ID {$prod['productId']} tidak ditemukan di cabang ini."]);
                }

                $remainingStock = $productLoc->inStock - $prod['quantity'];

                if ($prod['quantity'] > $productLoc->inStock) {
                    return responseInvalid([
                        "Stok produk '{$prod['productId']}' tidak mencukupi. Tersedia: {$productLoc->inStock}, Diminta: {$prod['quantity']}"
                    ]);
                }

                if ($remainingStock < $productLoc->lowStock) {
                    $lowStockWarnings[] = "Stok produk '{$prod['productId']}' akan di bawah batas minimum ({$productLoc->lowStock}). Sisa: {$remainingStock}";
                }
            }


            $freeItems = [];
            $lowStockWarnings = [];

            if (!empty($request->selectedPromos['freeItems'])) {
                try {
                    $promoResultFreeItems = $this->handleFreeItemsPromo(
                        $request->locationId,
                        $request->selectedPromos['freeItems'],
                        $request->user()->id
                    );

                    $freeItems = $promoResultFreeItems['freeItems'];
                    $lowStockWarnings = array_merge($lowStockWarnings, $promoResultFreeItems['lowStockWarnings']);
                } catch (\Exception $e) {
                    return responseInvalid([$e->getMessage()]);
                }
            }

            $discountedProducts = [];
            $discountDetails = [];
            $discountAmount = 0;

            if (!empty($request->selectedPromos['discounts'])) {
                try {
                    $promoResultDiscounts = $this->handleDiscountPromos(
                        $request->locationId,
                        $request->selectedPromos['discounts'],
                        $request->productList
                    );

                    $discountedProducts = $promoResultDiscounts['discountedProducts'];
                    $discountDetails = $promoResultDiscounts['discountDetails'];
                    $discountAmount = $promoResultDiscounts['totalDiscount'];

                    // Gabungkan catatan promo diskon ke promoNotes
                    $promoNotes = array_merge($promoNotes, $discountDetails);

                    // Tambahkan total diskon ke akumulasi diskon
                    $totalDiscount += $discountAmount;
                } catch (\Exception $e) {
                    return responseInvalid([$e->getMessage()]);
                }
            }


            $trxCount = DB::table('transactionpetshop')
                ->where('locationId', $request->locationId)
                ->count();
            $regisNo = 'RPC.TRX.' . $request->locationId . '.' . str_pad($trxCount + 1, 8, '0', STR_PAD_LEFT);

            $totalAmount = 0;
            $totalPayment = 0;

            if (!empty($request->selectedPromos['freeItems'])) {
                foreach ($request->selectedPromos['freeItems'] as $promoId) {
                    DB::table('promotionFreeItems')
                        ->where('promoMasterId', $promoId)
                        ->where('totalMaxUsage', '>', 0)
                        ->decrement('totalMaxUsage', 1);
                }
            }

            if (!empty($promoResult['purchases'])) {
                $totalAmount = $promoResult['subtotal'] ?? 0;
                $totalPayment = $promoResult['total_payment'] ?? 0;
            } else {
                $totalAmount = 0;
                foreach ($request->productList as $prod) {
                    $totalAmount += $prod['quantity'] * $prod['price'];
                }
                $totalPayment = $totalAmount;
            }


            $totalUsePromo = 0;

            if (!empty($request->selectedPromos)) {
                $totalUsePromo += count($request->selectedPromos['freeItems'] ?? []);
                $totalUsePromo += count($request->selectedPromos['discounts'] ?? []);
                $totalUsePromo += count($request->selectedPromos['bundles'] ?? []);
                $totalUsePromo += count($request->selectedPromos['basedSales'] ?? []);
            }

            $tranId = DB::table('transactionpetshop')->insertGetId([
                'registrationNo' => $regisNo,
                'locationId' => $request->locationId,
                'customerId' => $cust,
                'note' => $request->notes,
                'paymentMethod' => $request->paymentMethod,
                'userId' => $request->user()->id,
                'totalAmount' => $totalAmount,
                'totalDiscount' => $totalDiscount,
                'totalPayment' => $totalPayment,
                'promoNotes' => !empty($promoNotes) ? json_encode($promoNotes) : null,
                'isPayed' => true,
                'totalUsePromo' => $totalUsePromo,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $tran = (object) [
                'id' => $tranId
            ];

            $totalItem = 0;


            if (!empty($request->selectedPromos['freeItems'])) {
                $freeItems = DB::table('promotionFreeItems')
                    ->select('promotionFreeItems.*', 'promotionMasters.id as promoId')
                    ->join('promotionMasters', 'promotionMasters.id', '=', 'promotionFreeItems.promoMasterId')
                    ->whereIn('promotionMasters.id', $request->selectedPromos['freeItems'])
                    ->get();

                foreach ($freeItems as $freeItem) {

                    $productBoughtExists = false;
                    foreach ($request->productList as $prod) {
                        if ($prod['productId'] == $freeItem->productBuyId) {
                            $productBoughtExists = true;
                            break;
                        }
                    }

                    if ($productBoughtExists) {

                        DB::table('productLocations')
                            ->where('locationId', $request->locationId)
                            ->where('productId', $freeItem->productFreeId)
                            ->decrement('inStock', $freeItem->quantityFreeItem);

                        DB::table('productLocations')
                            ->where('locationId', $request->locationId)
                            ->where('productId', $freeItem->productFreeId)
                            ->decrement('diffStock', $freeItem->quantityFreeItem);


                        DB::table('transactionpetshopdetail')->insert([
                            'transactionpetshopId' => $tran->id,
                            'productId' => $freeItem->productFreeId,
                            'quantity' => $freeItem->quantityFreeItem,
                            'price' => 0,
                            'discount' => 0,
                            'final_price' => 0,
                            'promoId' => $freeItem->promoId,
                            'isDeleted' => false,
                            'userId' => $request->user()->id,
                            'userUpdateId' => $request->user()->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $totalItem += $freeItem->quantityFreeItem;
                    }
                }
            }

            if (!empty($promoResult['purchases'])) {
                foreach ($promoResult['purchases'] as $purchase) {
                    $productId = null;
                    $note = $purchase['note'] ?? '';


                    if (!empty($purchase['sku'])) {
                        $product = DB::table('products')
                            ->where('sku', $purchase['sku'])
                            ->first();

                        if ($product) {
                            $productId = $product->id;
                        } else {

                            continue;
                        }
                    } else {

                        continue;
                    }


                    if (isset($purchase['included_items'])) {
                        $includedItems = [];
                        foreach ($purchase['included_items'] as $item) {
                            $includedItems[] = $item['name'] . " (Rp" . number_format($item['normal_price']) . ")";
                        }
                        $note .= " Includes: " . implode(", ", $includedItems);
                    }


                    $unitPrice = $purchase['unit_price'] ?? ($purchase['total'] / $purchase['quantity']);
                    $discount = $purchase['discount'] ?? 0;
                    $finalPrice = $unitPrice;

                    if ($discount > 0) {
                        if ($discount <= 100) {
                            $finalPrice = $unitPrice - ($unitPrice * ($discount / 100));
                        } else {
                            $finalPrice = $unitPrice - $discount;
                        }
                    }

                    $bonusQuantity = $purchase['bonus'] ?? 0;
                    $totalQuantity = $purchase['quantity'];


                    DB::table('transactionpetshopdetail')->insert([
                        'transactionpetshopId' => $tran->id,
                        'productId' => $productId,
                        'quantity' => $totalQuantity,
                        'price' => $unitPrice,
                        'discount' => $discount,
                        'final_price' => $finalPrice,
                        'promoId' => null,
                        'note' => $note,
                        'isDeleted' => false,
                        'userId' => $request->user()->id,
                        'userUpdateId' => $request->user()->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $totalItem += $totalQuantity;


                    DB::table('productLocations')
                        ->where('locationId', $request->locationId)
                        ->where('productId', $productId)
                        ->decrement('inStock', $totalQuantity);

                    DB::table('productLocations')
                        ->where('locationId', $request->locationId)
                        ->where('productId', $productId)
                        ->decrement('diffStock', $totalQuantity);
                }
            } else {
                foreach ($request->productList as $prod) {
                    $discounted = collect($discountedProducts)->firstWhere('productId', $prod['productId']);

                    $unitPrice = $prod['price'];
                    $discount = $discounted['discount'] ?? 0;
                    $finalPrice = $discounted['finalPrice'] ?? $unitPrice;
                    $promoId = $discounted['promoId'] ?? ($prod['promoId'] ?? null);

                    DB::table('transactionpetshopdetail')->insert([
                        'transactionpetshopId' => $tran->id,
                        'productId' => $prod['productId'],
                        'quantity' => $prod['quantity'],
                        'price' => $unitPrice,
                        'discount' => $discount,
                        'final_price' => $finalPrice,
                        'promoId' => $promoId,
                        'isDeleted' => false,
                        'userId' => $request->user()->id,
                        'userUpdateId' => $request->user()->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $totalItem += $prod['quantity'];

                    DB::table('productLocations')
                        ->where('locationId', $request->locationId)
                        ->where('productId', $prod['productId'])
                        ->decrement('inStock', $prod['quantity']);

                    DB::table('productLocations')
                        ->where('locationId', $request->locationId)
                        ->where('productId', $prod['productId'])
                        ->decrement('diffStock', $prod['quantity']);
                }
            }


            DB::table('transactionpetshop')
                ->where('id', $tran->id)
                ->update([
                    'totalItem' => $totalItem,
                ]);

            transactionLog($tran->id, 'New Transaction', '', $request->user()->id);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Transaksi berhasil dibuat.',
                'lowStockWarnings' => $lowStockWarnings,
                'transactionId' => $tran->id,
                'promoApplied' => !empty($promoResult) ? true : false,
                'totalUsePromo' => $totalUsePromo,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return responseInvalid([$e->getMessage()]);
        }
    }

    private function handleFreeItemsPromo($locationId, $promoIds, $userId)
    {
        $lowStockWarnings = [];

        $freeItems = DB::table('promotionFreeItems')
            ->select('promotionFreeItems.*', 'promotionMasters.name')
            ->join('promotionMasters', 'promotionMasters.id', '=', 'promotionFreeItems.promoMasterId')
            ->whereIn('promotionMasters.id', $promoIds)
            ->get();


        foreach ($freeItems as $freeItem) {
            $productLoc = DB::table('productLocations')
                ->where('locationId', $locationId)
                ->where('productId', $freeItem->productFreeId)
                ->first();

            if (!$productLoc) {
                throw new \Exception("Produk bonus ID {$freeItem->productFreeId} tidak ditemukan di cabang ini.");
            }

            $remainingStock = $productLoc->inStock - $freeItem->quantityFreeItem;

            if ($freeItem->quantityFreeItem > $productLoc->inStock) {
                throw new \Exception("Stok produk bonus '{$freeItem->productFreeId}' tidak mencukupi. Tersedia: {$productLoc->inStock}, Dibutuhkan: {$freeItem->quantityFreeItem}");
            }

            if ($remainingStock < $productLoc->lowStock) {
                $lowStockWarnings[] = "Stok produk bonus '{$freeItem->productFreeId}' akan di bawah batas minimum ({$productLoc->lowStock}). Sisa: {$remainingStock}";
            }

            if ($freeItem->totalMaxUsage <= 0) {
                $promoName = $freeItem->name ?? "Promo tidak dikenal";
                throw new \Exception("Promo '{$promoName}' sudah tidak bisa digunakan (habis).");
            }


            DB::table('promotionFreeItems')
                ->where('id', $freeItem->id)
                ->where('totalMaxUsage', '>', 0)
                ->decrement('totalMaxUsage', 1);
        }

        return [
            'freeItems' => $freeItems,
            'lowStockWarnings' => $lowStockWarnings
        ];
    }

    private function handleDiscountPromos($locationId, $discountPromoIds, $productList)
    {
        $discountItems = DB::table('promotionDiscounts')
            ->whereIn('promoMasterId', $discountPromoIds)
            ->get();

        $discountedProducts = [];
        $discountDetails = [];
        $totalDiscount =  0;

        foreach ($productList as $prod) {
            foreach ($discountItems as $promo) {
                if ($promo->productId == $prod['productId']) {
                    $unitPrice = $prod['price'];
                    $discount = 0;
                    $finalPrice = $unitPrice;

                    if ($promo->percentOrAmount === 'percent') {
                        $discount = $promo->percent;
                        $finalPrice = $unitPrice - ($unitPrice * ($discount / 100));
                    } elseif ($promo->percentOrAmount === 'amount') {
                        $discount = $promo->amount;
                        $finalPrice = $unitPrice - $discount;
                        if ($finalPrice < 0) $finalPrice = 0;
                    }

                    $totalDiscount += ($unitPrice - $finalPrice) * $prod['quantity'];

                    $discountedProducts[] = [
                        'productId' => $prod['productId'],
                        'quantity' => $prod['quantity'],
                        'originalPrice' => $unitPrice,
                        'discount' => $discount,
                        'finalPrice' => $finalPrice,
                        'promoId' => $promo->promoMasterId,
                    ];

                    $discountDetails[] = "Diskon untuk produk ID {$prod['productId']}: potongan " .
                        ($promo->percentOrAmount === 'percent' ? "{$discount}%" : "Rp " . number_format($discount));
                }
            }
        }

        return [
            'discountedProducts' => $discountedProducts,
            'discountDetails' => $discountDetails,
            'totalDiscount' => $totalDiscount,
        ];
    }



    public function delete(Request $request)
    {
        foreach ($request->id as $va) {
            $tran = TransactionPetShop::find($va);

            if (!$tran) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['Transaksi tidak ditemukan.'],
                ], 422);
            }
        }

        foreach ($request->id as $va) {
            $tran = TransactionPetShop::find($va);

            $tran->deletedBy = $request->user()->id;
            $tran->isDeleted = true;
            $tran->deletedAt = Carbon::now();
            $tran->save();

            transactionLog($va, 'Transaction Deleted', '', $request->user()->id);
        }

        return responseDelete();
    }

    public function export(Request $request)
    {
        if ($request->user()->roleId != 1) {
            return response()->json([
                'message' => 'Unauthorized. Only admin can export data.'
            ], 403);
        }

        $data = DB::table('transactionpetshop as tp')
            ->join('customer as c', 'tp.customerId', '=', 'c.id')
            ->join('location as l', 'tp.locationId', '=', 'l.id')
            ->join('customerGroups as cg', 'c.customerGroupId', '=', 'cg.id')
            ->leftJoin('users as u', 'tp.userId', '=', 'u.id')
            ->leftJoin('paymentmethod as pm', 'tp.paymentMethod', '=', 'pm.id')
            ->where('tp.isDeleted', '=', 0)
            ->select(
                'tp.id',
                'tp.registrationNo',
                'l.locationName',
                'c.nickName as customerName',
                'cg.customerGroup',
                'pm.name as paymentMethod',
                'tp.created_at',
                'u.nickName as createdBy'
            )
            ->get();

        foreach ($data as $item) {
            $item->totalUsePromo = DB::table('transactionpetshopdetail')
                ->where('transactionpetshopId', $item->id)
                ->whereNotNull('promoId')
                ->count();

            $item->totalItem = DB::table('transactionpetshopdetail')
                ->where('transactionpetshopId', $item->id)
                ->sum('quantity');

            $item->totalAmount = DB::table('transactionpetshopdetail')
                ->where('transactionpetshopId', $item->id)
                ->select(DB::raw('SUM(quantity * price) as total'))
                ->value('total');
        }

        $spreadsheet = IOFactory::load(public_path() . '/template/transaction/' . 'Template_Export_Transaction_Pet_Shop.xlsx');
        $sheet = $spreadsheet->getSheet(0);

        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Transaction No');
        $sheet->setCellValue('C1', 'Cabang');
        $sheet->setCellValue('D1', 'Customer Name');
        $sheet->setCellValue('E1', 'Customer Group');
        $sheet->setCellValue('F1', 'Total Use Promo');
        $sheet->setCellValue('G1', 'Total Item');
        $sheet->setCellValue('H1', 'Amount Transaction');
        $sheet->setCellValue('I1', 'Payment Method');
        $sheet->setCellValue('J1', 'Dibuat Pada');
        $sheet->setCellValue('K1', 'Dibuat Oleh');

        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $sheet->getStyle('A1:K1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A1:K1')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        $row = 2;
        $no = 1;
        foreach ($data as $item) {
            $sheet->setCellValue("A{$row}", $no);
            $sheet->setCellValue("B{$row}", $item->registrationNo);
            $sheet->setCellValue("C{$row}", $item->locationName);
            $sheet->setCellValue("D{$row}", $item->customerName);
            $sheet->setCellValue("E{$row}", $item->customerGroup);
            $sheet->setCellValue("F{$row}", $item->totalUsePromo ?? 0);
            $sheet->setCellValue("G{$row}", $item->totalItem ?? 0);
            $sheet->setCellValue("H{$row}", $item->totalAmount ?? 0);
            $sheet->setCellValue("I{$row}", $item->paymentMethod ?? '-');
            $sheet->setCellValue("J{$row}", $item->created_at);
            $sheet->setCellValue("K{$row}", $item->createdBy ?? '-');

            $sheet->getStyle("A{$row}:K{$row}")
                ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

            $sheet->getStyle("A{$row}:K{$row}")->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

            $row++;
            $no++;
        }

        foreach (range('A', 'K') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $newFilePath = public_path() . '/template_download/' . 'Export Transaction Pet Shop.xlsx';
        $writer->save($newFilePath);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Export Transaction Pet Shop.xlsx"',
        ]);
    }

    public function transactionDiscount(Request $request)
    {
        $products = json_decode($request->products, true);
        $freeItems = json_decode($request->freeItems, true);
        $discounts = json_decode($request->discounts, true);
        $bundles = json_decode($request->bundles, true);
        $basedSales = json_decode($request->basedSales, true);

        $results = [];
        $promoNotes = [];
        $subtotal = 0;
        $totalDiscount = 0;

        foreach ($products as $value) {

            foreach ($freeItems as $free) {
                Log::debug('Checking free item promo:', ['freePromoId' => $free, 'productId' => $value['productId']]);

                $res = DB::table('promotionMasters as pm')
                    ->join('promotionFreeItems as fi', 'pm.id', 'fi.promoMasterId')
                    ->join('products as pbuy', 'pbuy.id', 'fi.productBuyId')
                    ->join('products as pfree', 'pfree.id', 'fi.productFreeId')
                    ->select(
                        'pbuy.fullName as item_name',
                        'pfree.id as free_product_id',
                        'pbuy.category',
                        'fi.quantityBuyItem as quantity',
                        'fi.quantityFreeItem as bonus',
                        DB::raw('0 as discount'),
                        DB::raw($value['eachPrice'] . ' as unit_price'),
                        DB::raw($value['priceOverall'] . ' as total'),
                        DB::raw("CONCAT('Buy ', fi.quantityBuyItem, ' Get ', fi.quantityFreeItem, ' Free for ', pbuy.fullName) as note")
                    )
                    ->where('pm.id', '=', $free)
                    ->where('pbuy.id', '=', $value['productId'])
                    ->get();

                if ($res->isEmpty()) {
                    Log::debug("Promo ID $free tidak cocok dengan productId {$value['productId']}");

                    Log::debug('Checking promo match:', [
                        'promoId' => $free,
                        'productId' => $value['productId']
                    ]);
                }
                foreach ($res as $item) {
                    $results[] = (array)$item;
                    $subtotal += $item->total;
                    $promoNotes[] = $item->note;
                }
            }

            foreach ($bundles as $bundle) {

                $bundleData = DB::table('promotionMasters as pm')
                    ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                    ->join('promotionBundles as pb', 'pm.id', 'pb.promoMasterId')
                    ->select(
                        'pm.name as item_name',
                        DB::raw('"" as category'),
                        DB::raw('1 as quantity'),
                        DB::raw('0 as bonus'),
                        DB::raw('0 as discount'),
                        'pb.price as total',
                        'pb.id as promoBundleId',
                    )
                    ->where('pm.id', '=', $bundle)
                    ->where('pl.locationId', '=', $value['locationId'])
                    ->first();

                if (!$bundleData) continue;

                $includedItems = DB::table('promotionBundleDetails as pbd')
                    ->join('products as p', 'p.id', '=', 'pbd.productId')
                    ->where('pbd.promoBundleId', '=', $bundleData->promoBundleId)
                    ->select('p.fullName as name', 'p.price as normal_price')
                    ->get()
                    ->toArray();

                // Hitung nilai normal total
                $normalTotal = array_sum(array_column($includedItems, 'normal_price'));
                $bundleNote = $bundleData->item_name . " only Rp" . number_format($bundleData->total, 0, ',', '.') .
                    " (save Rp" . number_format($normalTotal - $bundleData->total, 0, ',', '.') . ")";


                $results[] = [
                    'item_name' => $bundleData->item_name,
                    'free_product_id' => $item->free_product_id,
                    'category' => $bundleData->category,
                    'quantity' => $bundleData->quantity,
                    'bonus' => $bundleData->bonus,
                    'discount' => $bundleData->discount,
                    'total' => $bundleData->total,
                    'included_items' => $includedItems
                ];

                $subtotal += $bundleData->total;
                $promoNotes[] = $bundleNote;
            }

            foreach ($discounts as $disc) {

                $data = DB::table('promotionMasters as pm')
                    ->join('promotionDiscounts as pd', 'pm.id', 'pd.promoMasterId')
                    ->join('products as p', 'p.id', 'pd.productId')
                    ->select(
                        'p.fullName as item_name',
                        'p.category',
                        DB::raw($value['quantity'] . ' as quantity'),
                        DB::raw('0 as bonus'),
                        DB::raw("CASE WHEN pd.percentOrAmount = 'percent' THEN pd.percent ELSE pd.amount END as discount"),
                        DB::raw($value['eachPrice'] . ' as unit_price'),
                        DB::raw($value['priceOverall'] . ' as total'),
                        'pd.percentOrAmount',
                        'pd.percent',
                        'pd.amount'
                    )
                    ->where('pm.id', '=', $disc)
                    ->first();

                if (!$data) continue;

                if ($data->percentOrAmount === 'percent') {
                    $discountNote = $data->percent . '% discount on ' . $data->item_name . ' (save Rp' . number_format($data->amount * $value['quantity'], 0, ',', '.') . ')';
                    $saved = $data->amount * $value['quantity'];
                } else {
                    $discountNote = 'Rp' . number_format($data->amount, 0, ',', '.') . ' discount on ' . $data->item_name;
                    $saved = $data->amount * $value['quantity'];
                }

                $results[] = [
                    'item_name' => $data->item_name,
                    'category' => $data->category,
                    'quantity' => $data->quantity,
                    'bonus' => $data->bonus,
                    'discount' => $data->discount,
                    'total' => $data->total,
                    'note' => $discountNote,
                ];

                $subtotal += $data->total;
                $totalDiscount += $saved;
                $promoNotes[] = $discountNote;
            }
        }

        // Misalnya ada diskon tambahan jika subtotal > 200rb
        if ($subtotal > 200000) {
            $totalDiscount += 10000;
            $promoNotes[] = 'Rp10,000 discount for purchases over Rp200,000';
            $discountNote = 'Diskon Nominal (Belanja > Rp 200.000)';
        } else {
            $discountNote = '';
        }

        return [
            'purchases' => $results,
            'subtotal' => $subtotal,
            'discount_note' => $discountNote,
            'total_discount' => $totalDiscount,
            'total_payment' => $subtotal - $totalDiscount,
            'promo_notes' => $promoNotes
        ];
    }

    public function getTransactionDetails(Request $request)
    {
        $transactionId = $request->input('transactionId');

        $details = DB::table('transactionPetshopDetail as d')
            ->join('products as p', 'p.id', '=', 'd.productId')
            ->select(
                'd.id',
                'd.transactionpetshopId',
                'd.productId',
                'p.fullName as productName',
                'd.quantity',
                'd.price',
                'd.discount',
                'd.final_price',
                'd.promoId'
            )
            ->where('d.transactionpetshopId', $transactionId)
            ->where('d.isDeleted', false)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $details
        ]);
    }
}
