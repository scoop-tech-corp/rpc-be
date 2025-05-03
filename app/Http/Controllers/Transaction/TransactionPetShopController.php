<?php

namespace App\Http\Controllers\Transaction;

use DB;
use Validator;
use Carbon\Carbon;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Models\ProductLocations;
use App\Models\Customer\Customer;
use App\Models\TransactionPetShop;
use App\Models\Staff\UsersLocation;
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
            ->join('customergroups as cg', 'c.customerGroupId', '=', 'cg.id')
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
                $cust = Customer::create([
                    'firstName' => $request->customerName,
                    'locationId' => $request->locationId,
                    'typeId' => 0,
                    'memberNo' => '',
                    'gender' => '',
                    'joinDate' => Carbon::now(),
                    'createdBy' => $request->user()->id,
                    'userUpdateId' => $request->user()->id
                ]);
            } else {
                $cust = Customer::select('id', 'isDeleted')
                    ->where('id', $request->customerId)
                    ->where('isDeleted', 0)
                    ->first();

                if (!$cust) {
                    return responseInvalid(['Customer not found.']);
                }
            }


            $lowStockWarnings = [];
            foreach ($request->productList as $prod) {
                $productLoc = ProductLocations::where('locationId', $request->locationId)
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

            $trxCount = TransactionPetShop::where('locationId', $request->locationId)->count();
            $regisNo = 'RPC.TRX.' . $request->locationId . '.' . str_pad($trxCount + 1, 8, '0', STR_PAD_LEFT);

            $tran = TransactionPetShop::create([
                'registrationNo' => $regisNo,
                'isNewCustomer' => $request->isNewCustomer,
                'locationId' => $request->locationId,
                'customerId' => $cust->id,
                'registrant' => $request->registrant,
                'note' => $request->notes,
                'paymentMethod' => $request->paymentMethod,
                'userId' => $request->user()->id,
            ]);

            $totalItem = 0;
            $totalUsePromo = 0;
            $totalAmount = 0;

            foreach ($request->productList as $prod) {
                TransactionPetShopDetail::create([
                    'transactionpetshopId' => $tran->id,
                    'productId' => $prod['productId'],
                    'quantity' => $prod['quantity'],
                    'price' => $prod['price'],
                    'note' => $prod['note'] ?? null,
                    'promoId' => $prod['promoId'] ?? null,
                    'isDeleted' => false,
                    'userId' => $request->user()->id,
                    'userUpdateId' => $request->user()->id,
                ]);

                $totalItem += $prod['quantity'];
                if (!empty($prod['promoId'])) $totalUsePromo++;
                $totalAmount += $prod['quantity'] * $prod['price'];

                ProductLocations::where('locationId', $request->locationId)
                    ->where('productId', $prod['productId'])
                    ->decrement('inStock', $prod['quantity']);

                ProductLocations::where('locationId', $request->locationId)
                    ->where('productId', $prod['productId'])
                    ->decrement('diffStock', $prod['quantity']);
            }

            $tran->update([
                'totalItem' => $totalItem,
                'totalUsePromo' => $totalUsePromo,
                'totalAmount' => $totalAmount,
            ]);

            transactionLog($tran->id, 'New Transaction', '', $request->user()->id);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Transaksi berhasil dibuat.',
                'lowStockWarnings' => $lowStockWarnings
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return responseInvalid([$e->getMessage()]);
        }
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
            ->join('customergroups as cg', 'c.customerGroupId', '=', 'cg.id')
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
}
