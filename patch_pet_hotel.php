<?php
$file = '/Users/ahmadmukhtar/Projects/rpc-be/app/Http/Controllers/Transaction/PetHotelController.php';
$content = file_get_contents($file);

$startMarker = '    public function transactionDiscount(Request $request)';
$endMarker = '    public function payment(Request $request)';

$posStart = strpos($content, $startMarker);
$posEnd = strpos($content, $endMarker);

if ($posStart === false || $posEnd === false) {
    echo "Markers not found\n";
    exit(1);
}

$replacement = <<<'REPLACE'
    public function transactionDiscount(Request $request)
    {
        $services = $this->ensureIsArray($request->services) ?? [];
        $products = $this->ensureIsArray($request->products) ?? [];
        $freeItems = $this->ensureIsArray($request->freeItems) ?? [];
        $discounts = $this->ensureIsArray($request->discounts) ?? [];
        $bundles = $this->ensureIsArray($request->bundles) ?? [];

        $results = [];
        $promoNotes = [];
        $subtotal = 0;
        $totalDiscount = 0;

        $trans = TransactionPetHotel::find($request->transactionId);
        if (!$trans) {
            return response()->json(['message' => 'Transaction not found'], 404);
        }

        // --- PRE-FETCH PROMOTIONS FOR BATCH PROCESSING ---
        $promoServiceDiscounts = [];
        if (!empty($discounts) && !empty($services)) {
            $serviceIds = array_column($services, 'serviceId');
            $data = DB::table('promotionMasters as pm')
                ->join('promotion_discount_services as pd', 'pm.id', 'pd.promoMasterId')
                ->join('services as s', 's.id', 'pd.serviceId')
                ->join('serviceCategory as sc', 's.type', 'sc.id')
                ->select(
                    'pm.id as promoId', 's.id as serviceId', 's.fullName as item_name', 's.type as category',
                    'pd.discountType', 'pd.percent', 'pd.amount'
                )
                ->whereIn('pm.id', $discounts)
                ->whereIn('pd.serviceId', $serviceIds)
                ->get();
            foreach ($data as $d) {
                $promoServiceDiscounts[$d->serviceId][$d->promoId] = $d;
            }
        }

        $promoServiceBundles = [];
        if (!empty($bundles) && !empty($services)) {
            $serviceIds = array_column($services, 'serviceId');
            $data = DB::table('promotionMasters as pm')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->join('promotionBundles as pb', 'pm.id', 'pb.promoMasterId')
                ->join('promotion_bundle_detail_services as pbd', 'pb.id', 'pbd.promoBundleId')
                ->select('pm.id as promoId', 'pm.name as item_name', 'pb.price as total', 'pb.id as promoBundleId', 'pbd.serviceId')
                ->whereIn('pm.id', $bundles)
                ->whereIn('pbd.serviceId', $serviceIds)
                ->where('pl.locationId', '=', $trans->locationId)
                ->get();
                
            foreach ($data as $d) {
                $includedItems = DB::table('promotion_bundle_detail_services as pbd')
                    ->join('services as s', 's.id', '=', 'pbd.serviceId')
                    ->join('servicesPrice as sp', 'sp.serviceId', '=', 's.id')
                    ->where('pbd.promoBundleId', '=', $d->promoBundleId)
                    ->where('sp.location_id', '=', $trans->locationId)
                    ->select('s.id as serviceId', 's.fullName as name', 'sp.price as normal_price')
                    ->get()
                    ->toArray();
                $d->included_items = $includedItems;
                $promoServiceBundles[$d->serviceId][$d->promoId] = $d;
            }
        }

        $promoProductFreeItems = [];
        if (!empty($freeItems) && !empty($products)) {
            $productIds = array_column($products, 'productId');
            $data = DB::table('promotionMasters as pm')
                ->join('promotionFreeItems as fi', 'pm.id', 'fi.promoMasterId')
                ->join('products as pbuy', 'pbuy.id', 'fi.productBuyId')
                ->join('products as pfree', 'pfree.id', 'fi.productFreeId')
                ->select(
                    'pm.id as promoId', 'pbuy.fullName as item_name', 'pbuy.id as buy_product_id', 'pfree.id as free_product_id',
                    'pbuy.category', 'fi.quantityBuyItem', 'fi.quantityFreeItem', 'pfree.fullName as free_product_name'
                )
                ->whereIn('pm.id', $freeItems)
                ->whereIn('pbuy.id', $productIds)
                ->get();
            foreach ($data as $d) {
                $promoProductFreeItems[$d->buy_product_id][$d->promoId] = $d;
            }
        }

        $promoProductBundles = [];
        if (!empty($bundles) && !empty($products)) {
            $productIds = array_column($products, 'productId');
            $data = DB::table('promotionMasters as pm')
                ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
                ->join('promotionBundles as pb', 'pm.id', 'pb.promoMasterId')
                ->join('promotion_bundle_detail_products as pbd', 'pb.id', 'pbd.promoBundleId')
                ->select('pm.id as promoId', 'pm.name as item_name', 'pb.price as total', 'pb.id as promoBundleId', 'pbd.productId')
                ->whereIn('pm.id', $bundles)
                ->whereIn('pbd.productId', $productIds)
                ->where('pl.locationId', '=', $trans->locationId)
                ->get();
                
            foreach ($data as $d) {
                $includedItems = DB::table('promotion_bundle_detail_products as pbd')
                    ->join('products as p', 'p.id', '=', 'pbd.productId')
                    ->where('pbd.promoBundleId', '=', $d->promoBundleId)
                    ->select('p.id as productId', 'p.fullName as name', 'p.price as normal_price')
                    ->get()
                    ->toArray();
                $d->included_items = $includedItems;
                $promoProductBundles[$d->productId][$d->promoId] = $d;
            }
        }

        $promoProductDiscounts = [];
        if (!empty($discounts) && !empty($products)) {
            $productIds = array_column($products, 'productId');
            $data = DB::table('promotionMasters as pm')
                ->join('promotion_discount_products as pd', 'pm.id', 'pd.promoMasterId')
                ->join('products as p', 'p.id', 'pd.productId')
                ->select(
                    'pm.id as promoId', 'p.id as productId', 'p.fullName as item_name', 'p.category',
                    'pd.discountType', 'pd.percent', 'pd.amount'
                )
                ->whereIn('pm.id', $discounts)
                ->whereIn('pd.productId', $productIds)
                ->get();
            foreach ($data as $d) {
                $promoProductDiscounts[$d->productId][$d->promoId] = $d;
            }
        }

        // --- PROCESSING SERVICES ---
        foreach ($services as $value) {
            $isGetPromo = false;

            if ($request->has('discounts')) {
                foreach ($discounts as $disc) {
                    if (isset($promoServiceDiscounts[$value['serviceId']][$disc])) {
                        $data = $promoServiceDiscounts[$value['serviceId']][$disc];

                        if ($data->discountType === 'percent') {
                            $amount_discount = ($data->percent / 100) * $value['eachPrice'];
                            $discountNote = 'Diskon produk ' . $data->item_name . ' sebesar ' . $data->percent . '% (hemat Rp' . number_format($amount_discount, 0, ',', '.') . ')';
                            $saved = $amount_discount;
                        } else {
                            $discountNote = 'Diskon produk ' . $data->item_name . ' sebesar Rp' . number_format($data->amount, 0, ',', '.');
                            $saved = $data->amount;
                        }

                        $results[] = [
                            'item_name' => $data->item_name,
                            'category' => $data->category,
                            'quantity' => $value['quantity'],
                            'bonus' => 0,
                            'discount' => ($data->discountType === 'percent') ? $data->percent : $data->amount,
                            'unit_price' => $value['eachPrice'],
                            'total' => $value['priceOverall'] - $saved,
                            'promoId' => $data->promoId,
                            'serviceId' => $data->serviceId,
                            'promoCategory' => 'discount',
                        ];

                        $subtotal += ($value['priceOverall'] - $saved);
                        $totalDiscount += $saved;
                        $promoNotes[] = $discountNote;
                        $isGetPromo = true;
                    }
                }
            }

            if ($request->has('bundles')) {
                foreach ($bundles as $bundle) {
                    if (isset($promoServiceBundles[$value['serviceId']][$bundle])) {
                        $bundleData = $promoServiceBundles[$value['serviceId']][$bundle];

                        $normalTotal = array_sum(array_column($bundleData->included_items, 'normal_price'));
                        $bundleNote = $bundleData->item_name . " only Rp" . number_format($bundleData->total, 0, ',', '.') .
                            " (save Rp" . number_format($normalTotal - $bundleData->total, 0, ',', '.') . ")";

                        $results[] = [
                            'item_name' => $bundleData->item_name,
                            'category' => "",
                            'quantity' => 1,
                            'bonus' => 0,
                            'discount' => 0,
                            'total' => $bundleData->total,
                            'included_items' => $bundleData->included_items,
                            'promoId' => $bundleData->promoId,
                            'promoCategory' => 'bundle',
                        ];

                        $subtotal += $bundleData->total;
                        $promoNotes[] = $bundleNote;
                        $isGetPromo = true;
                    }
                }
            }

            if (!$isGetPromo) {
                $res = DB::table('services as p')
                    ->join('serviceCategory as sc', 'p.type', 'sc.id')
                    ->select(
                        DB::raw('NULL as promoId'),
                        'p.id as serviceId',
                        'p.fullName as item_name',
                        'sc.categoryName as category',
                        DB::raw($value['quantity'] . ' as quantity'),
                        DB::raw('0 as bonus'),
                        DB::raw('0 as discount'),
                        DB::raw($value['eachPrice'] . ' as unit_price'),
                        DB::raw($value['priceOverall'] . ' as total'),
                        DB::raw("'' as note")
                    )
                    ->where('p.id', '=', $value['serviceId'])
                    ->get();

                foreach ($res as $item) {
                    $results[] = (array)$item;
                    $subtotal += $item->total;
                }
            }
        }

        // --- PROCESSING PRODUCTS ---
        foreach ($products as $value) {
            $isGetPromo = false;

            if ($request->has('freeItems')) {
                foreach ($freeItems as $free) {
                    if (isset($promoProductFreeItems[$value['productId']][$free])) {
                        $data = $promoProductFreeItems[$value['productId']][$free];
                        
                        $note = 'Beli ' . $data->quantityBuyItem . ' ' . $data->item_name . ' Gratis ' . $data->quantityFreeItem . ' ' . $data->free_product_name;

                        $results[] = [
                            'promoId' => $data->promoId,
                            'item_name' => $data->item_name,
                            'buy_product_id' => $data->buy_product_id,
                            'free_product_id' => $data->free_product_id,
                            'category' => $data->category,
                            'quantity' => $data->quantityBuyItem,
                            'bonus' => $data->quantityFreeItem,
                            'discount' => 0,
                            'unit_price' => $value['eachPrice'],
                            'total' => $value['priceOverall'],
                            'note' => $note,
                            'promoCategory' => 'freeItem',
                        ];
                        
                        $subtotal += $value['priceOverall'];
                        $promoNotes[] = $note;
                        $isGetPromo = true;
                    }
                }
            }

            if ($request->has('bundles')) {
                foreach ($bundles as $bundle) {
                    if (isset($promoProductBundles[$value['productId']][$bundle])) {
                        $bundleData = $promoProductBundles[$value['productId']][$bundle];

                        $normalTotal = array_sum(array_column($bundleData->included_items, 'normal_price'));
                        $bundleNote = $bundleData->item_name . " only Rp" . number_format($bundleData->total, 0, ',', '.') .
                            " (save Rp" . number_format($normalTotal - $bundleData->total, 0, ',', '.') . ")";

                        $results[] = [
                            'item_name' => $bundleData->item_name,
                            'category' => "",
                            'quantity' => 1,
                            'bonus' => 0,
                            'discount' => 0,
                            'total' => $bundleData->total,
                            'included_items' => $bundleData->included_items,
                            'promoId' => $bundleData->promoId,
                            'promoCategory' => 'bundle',
                        ];

                        $subtotal += $bundleData->total;
                        $promoNotes[] = $bundleNote;
                        $isGetPromo = true;
                    }
                }
            }

            if ($request->has('discounts')) {
                foreach ($discounts as $disc) {
                    if (isset($promoProductDiscounts[$value['productId']][$disc])) {
                        $data = $promoProductDiscounts[$value['productId']][$disc];

                        if ($data->discountType === 'percent') {
                            $amount_discount = ($data->percent / 100) * $value['eachPrice'];
                            $discountNote = 'Diskon produk ' . $data->item_name . ' sebesar ' . $data->percent . '% (hemat Rp' . number_format($amount_discount, 0, ',', '.') . ')';
                            $saved = $amount_discount;
                        } else {
                            $discountNote = 'Diskon produk ' . $data->item_name . ' sebesar Rp' . number_format($data->amount, 0, ',', '.');
                            $saved = $data->amount * $value['quantity'];
                        }

                        $existingIdx = collect($results)->search(function($item) use ($data) {
                            return $item['item_name'] === $data->item_name && isset($item['promoCategory']) && $item['promoCategory'] == 'discount';
                        });

                        if ($existingIdx === false) {
                            $results[] = [
                                'item_name' => $data->item_name,
                                'category' => $data->category,
                                'quantity' => $value['quantity'],
                                'bonus' => 0,
                                'discountType' => $data->discountType,
                                'discount' => ($data->discountType === 'percent') ? $data->percent : $data->amount,
                                'total' => $value['priceOverall'] - $saved,
                                'note' => $discountNote,
                                'promoId' => $data->promoId,
                                'productId' => $data->productId,
                                'promoCategory' => 'discount',
                            ];

                            $subtotal += ($value['priceOverall'] - $saved);
                            $totalDiscount += $saved;
                            $promoNotes[] = $discountNote;
                        }
                        $isGetPromo = true;
                    }
                }
            }

            if (!$isGetPromo) {
                $res = DB::table('products as p')
                    ->select(
                        'p.id as productId',
                        DB::raw('NULL as promoId'),
                        'p.fullName as item_name',
                        'p.category',
                        DB::raw($value['quantity'] . ' as quantity'),
                        DB::raw('0 as bonus'),
                        DB::raw('0 as discount'),
                        DB::raw($value['eachPrice'] . ' as unit_price'),
                        DB::raw($value['priceOverall'] . ' as total'),
                        DB::raw("'' as note")
                    )
                    ->where('p.id', '=', $value['productId'])
                    ->get();

                foreach ($res as $item) {
                    $results[] = (array)$item;
                    $subtotal += $item->total;
                }
            }
        }

        $discount_based_sales = 0;
        $discountNote = '';
        if ($request->basedSale) {
            $res = DB::table('promotionMasters as pm')
                ->join('promotionBasedSales as pb', 'pm.id', 'pb.promoMasterId')
                ->select(
                    'pm.name', 'pb.minPurchase',
                    DB::raw("CASE WHEN percentOrAmount = 'amount' THEN 'amount' WHEN percentOrAmount = 'percent' THEN 'percent' ELSE '' END as discountType"),
                    DB::raw("CASE WHEN percentOrAmount = 'amount' THEN amount WHEN percentOrAmount = 'percent' THEN percent ELSE 0 END as totaldiscount")
                )
                ->where('pm.id', '=', $request->basedSale)
                ->where('minPurchase', '<=', $subtotal)
                ->where('maxPurchase', '>=', $subtotal)
                ->first();

            if ($res) {
                if ($res->discountType == 'amount') {
                    $discount_based_sales = $res->totaldiscount;
                    $promoNotes[] = 'Diskon Rp ' . $res->totaldiscount . ' untuk pembelian lebih dari Rp ' . $res->minPurchase;
                    $discountNote = 'Diskon Nominal (Belanja > Rp ' . $res->minPurchase . ')';
                    $totalDiscount = $res->totaldiscount;
                } else if ($res->discountType == 'percent') {
                    $discount_based_sales = $subtotal * ($res->totaldiscount / 100);
                    $promoNotes[] = 'Diskon ' . $res->totaldiscount . '% untuk pembelian lebih dari Rp ' . $res->minPurchase;
                    $discountNote = 'Diskon ' . $res->totaldiscount . ' % (Belanja > Rp ' . $res->minPurchase . ')';
                    $totalDiscount = $res->totaldiscount;
                }
            }
        }

        $response = [
            'purchases' => $results,
            'subtotal' => $subtotal,
            'discount_note' => $discountNote,
            'discount_based_sales' => floatval($discount_based_sales),
            'total_discount' => floatval($totalDiscount),
            'total_payment' => $subtotal - $totalDiscount,
            'promo_notes' => $promoNotes,
        ];
        
        if ($request->basedSale) {
            $response['promoBasedSaleId'] = $request->basedSale;
        }

        return response()->json($response);
    }
REPLACE;

$newContent = substr_replace($content, $replacement . "\n", $posStart, $posEnd - $posStart);
file_put_contents($file, $newContent);
echo "Successfully updated PetHotelController.php\n";
?>
