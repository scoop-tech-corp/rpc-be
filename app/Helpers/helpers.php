<?php

/**
 * Write code on Method
 *
 * @return response()
 */

use App\Models\Customer\Customer;
use App\Models\ProductLog;
use App\Models\Transaction;
use App\Models\ProductSellLog;
use App\Models\recentActivity;
use App\Models\TransactionLog;
use App\Models\ProductClinicLog;
use App\Models\productRestockLog;
use App\Models\ProductTransferLog;
use App\Models\TransactionBreeding;
use App\Models\TransactionPetHotel;
use App\Models\TransactionPetClinic;
use App\Models\TransactionPetshopLog;
use App\Models\TransactionBreedingLog;
use App\Models\TransactionPetHotelLog;
use App\Models\TransactionPetClinicLog;
use App\Models\transactionpetsalon;
use App\Models\transactionPetSalonLog;
use Carbon\Carbon;

if (!function_exists('adminAccess')) {
    function adminAccess(int $id): bool
    {
        $status = false;

        $user = DB::table('users as u')
            ->leftjoin('usersRoles as ur', 'ur.id', 'u.roleId')
            ->select('u.id', 'ur.roleName')
            ->where('u.id', '=', $id)
            ->first();

        if ($user->roleName == "Administrator" || $user->roleName == "Manager") {
            $status = true;
        }

        return $status;
    }
}

if (!function_exists('officeAccess')) {
    function officeAccess(int $id): bool
    {

        $user = DB::table('users as u')
            ->leftjoin('usersRoles as ur', 'ur.id', 'u.roleId')
            ->select('u.id', 'ur.roleName')
            ->where('u.id', '=', $id)
            ->first();

        if ($user->roleName != "Office") {
            return false;
        } else {
            return true;
        }
    }
}


if (!function_exists('managerAccess')) {
    function managerAccess(int $id): bool
    {
        $user = DB::table('users as u')
            ->leftjoin('usersRoles as ur', 'ur.id', 'u.roleId')
            ->select('u.id', 'ur.roleName')
            ->where('u.id', '=', $id)
            ->first();

        if ($user->roleName != "Manager") {
            return false;
        } else {
            return true;
        }
    }
}

if (!function_exists('staffAccess')) {
    function staffAccess(int $id): bool
    {
        $user = DB::table('users as u')
            ->leftjoin('usersRoles as ur', 'ur.id', 'u.roleId')
            ->select('u.id', 'ur.roleName')
            ->where('u.id', '=', $id)
            ->first();

        if ($user->roleName != "Staff") {
            return false;
        } else {
            return true;
        }
    }
}

if (!function_exists('customerAccess')) {
    function customerAccess(int $id): bool
    {
        $user = DB::table('users as u')
            ->leftjoin('usersRoles as ur', 'ur.id', 'u.roleId')
            ->select('u.id', 'ur.roleName')
            ->where('u.id', '=', $id)
            ->first();

        if ($user->roleName != "Customer") {
            return false;
        } else {
            return true;
        }
    }
}

if (!function_exists('intershipAccess')) {
    function intershipAccess(int $id): bool
    {
        $user = DB::table('users as u')
            ->leftjoin('usersRoles as ur', 'ur.id', 'u.roleId')
            ->select('u.id', 'ur.roleName')
            ->where('u.id', '=', $id)
            ->first();

        if ($user->roleName != "Intership") {
            return false;
        } else {
            return true;
        }
    }
}

if (!function_exists('role')) {
    function role(int $id): string
    {
        $user = DB::table('users as u')
            ->leftjoin('usersRoles as ur', 'ur.id', 'u.roleId')
            ->select('u.id', 'ur.roleName')
            ->where('u.id', '=', $id)
            ->first();

        return $user->roleName;
    }
}



if (!function_exists('roleStaffLeave')) {
    function roleStaffLeave(int $id): int
    {
        $user = DB::table('users as u')
            ->leftjoin('usersRoles as ur', 'ur.id', 'u.roleId')
            ->select('u.id', 'ur.roleName')
            ->where('u.id', '=', $id)
            ->first();


        if ($user->roleName == "Administrator" || $user->roleName == "Office") {
            return 1;
        } else {

            return 2;
        }
    }
}


if (!function_exists('convertTrueFalse')) {
    function convertTrueFalse(mixed $value): ?int
    {
        if ($value == 'true' || $value == 'TRUE') {
            return 1;
        } elseif ($value == 'false' || $value == 'FALSE') {
            return 0;
        }
        return null;
    }
}

if (!function_exists('productSellLog')) {
    function productSellLog(int $productId, string $transaction, string $remark, int $quantity, int|float $balance, int $userId): void
    {
        ProductLog::create([
            'productId' => $productId,
            'transaction' => $transaction,
            'remark' => $remark,
            'quantity' => $quantity,
            'balance' => $balance,
            'userId' => $userId,
        ]);
    }
}

if (!function_exists('productClinicLog')) {
    function productClinicLog(int $productId, string $transaction, string $remark, int $quantity, int|float $balance, int $userId): void
    {
        ProductLog::create([
            'productId' => $productId,
            'transaction' => $transaction,
            'remark' => $remark,
            'quantity' => $quantity,
            'balance' => $balance,
            'userId' => $userId,
        ]);
    }
}

if (!function_exists('productRestockLog')) {
    function productRestockLog(int $productRestockId, string $event, string $detail, int $userId): void
    {
        productRestockLog::create([
            'productRestockId' => $productRestockId,
            'event' => $event,
            'details' => $detail,
            'userId' => $userId,
        ]);
    }
}

if (!function_exists('productTransferLog')) {
    function productTransferLog(int $productTransferId, string $event, string $detail, int $userId): void
    {
        productTransferLog::create([
            'productTransferId' => $productTransferId,
            'event' => $event,
            'details' => $detail,
            'userId' => $userId,
        ]);
    }
}

// if (!function_exists('transactionLog')) {
//     function transactionLog($transactionId, $activity, $remark, $userId)
//     {
//         TransactionLog::create([
//             'transactionId' => $transactionId,
//             'activity' => $activity,
//             'remark' => $remark,
//             'userId' => $userId,
//         ]);
//     }
// }

if (!function_exists('transactionPetClinicLog')) {
    function transactionPetClinicLog(int $transactionId, string $activity, string $remark, int $userId): void
    {
        TransactionPetClinicLog::create([
            'transactionId' => $transactionId,
            'activity' => $activity,
            'remark' => $remark,
            'userId' => $userId,
        ]);
    }
}


if (!function_exists('transactionPetHotelLog')) {
    function transactionPetHotelLog(int $transactionId, string $activity, string $remark, int $userId): void
    {
        TransactionPetHotelLog::create([
            'transactionId' => $transactionId,
            'activity' => $activity,
            'remark' => $remark,
            'userId' => $userId,
        ]);
    }
}

if (!function_exists('transactionBreedingLog')) {
    function transactionBreedingLog(int $transactionId, string $activity, string $remark, int $userId): void
    {
        TransactionBreedingLog::create([
            'transactionId' => $transactionId,
            'activity' => $activity,
            'remark' => $remark,
            'userId' => $userId,
        ]);
    }
}

if (!function_exists('transactionPetSalonLog')) {
    function transactionPetSalonLog(int $transactionId, string $activity, string $remark, int $userId): void
    {
        transactionPetSalonLog::create([
            'transactionId' => $transactionId,
            'activity' => $activity,
            'remark' => $remark,
            'userId' => $userId,
        ]);
    }
}

if (!function_exists('statusTransactionPetClinic')) {
    function statusTransactionPetClinic(int $transactionId, string $status, int $doctorId): void
    {
        if ($status == 'Cek Kondisi Pet') {
            TransactionPetClinic::where('id', '=', $transactionId)
                ->update([
                    'status' => $status,
                    'doctorId' => $doctorId,
                ]);
            return;
        } else {
            TransactionPetClinic::where('id', '=', $transactionId)
                ->update([
                    'status' => $status,
                ]);
        }
    }
}

if (!function_exists('updateLastTransaction')) {
    function updateLastTransaction(int $customerId): void
    {
        Customer::where('id', '=', $customerId)
            ->update([
                'lastTransaction' => Carbon::now(),
            ]);
        return;
    }
}

if (!function_exists('transactionPetshopLog')) {
    function transactionPetshopLog(int $transactionId, string $activity, string $remark, int $userId): void
    {
        TransactionPetshopLog::create([
            'transactionId' => $transactionId,
            'activity' => $activity,
            'remark' => $remark,
            'userId' => $userId,
        ]);
    }
}


if (!function_exists('statusTransaction')) {
    function statusTransaction(int $transactionId, string $status): void
    {
        TransactionPetClinic::where('id', '=', $transactionId)
            ->update([
                'status' => $status,
            ]);
    }
}

if (!function_exists('statusTransactionPetHotel')) {
    function statusTransactionPetHotel(int $transactionId, string $status, int $doctorId): void
    {
        if ($status == 'Cek Kondisi Pet') {
            TransactionPetHotel::where('id', '=', $transactionId)
                ->update([
                    'status' => $status,
                    'doctorId' => $doctorId,
                ]);
        } else {
            TransactionPetHotel::where('id', '=', $transactionId)
                ->update([
                    'status' => $status,
                ]);
        }
    }
}

if (!function_exists('statusTransactionBreeding')) {
    function statusTransactionBreeding(int $transactionId, string $status, int $doctorId): void
    {

        if ($status == 'Cek Kondisi Pet') {
            TransactionBreeding::where('id', '=', $transactionId)
                ->update([
                    'status' => $status,
                    'doctorId' => $doctorId,
                ]);
        } else {
            TransactionBreeding::where('id', '=', $transactionId)
                ->update([
                    'status' => $status,
                ]);
        }
    }
}

if (!function_exists('statusTransactionPetSalon')) {
    function statusTransactionPetSalon(int $transactionId, string $status, int $doctorId): void
    {
        if ($status == 'Cek Kondisi Pet') {
            transactionpetsalon::where('id', '=', $transactionId)
                ->update([
                    'status' => $status,
                    'doctorId' => $doctorId,
                ]);
        } else {
            transactionpetsalon::where('id', '=', $transactionId)
                ->update([
                    'status' => $status,
                ]);
        }
    }
}

if (!function_exists('recentActivities')) {
    function recentActivities(string $module, string $event, string $detail, int $userId): void
    {
        recentActivity::create([
            'module' => $module,
            'event' => $event,
            'detail' => $detail,
            'userId' => $userId,
        ]);
    }
}

if (!function_exists('responseInvalid')) {
    function responseInvalid(array $errors): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => $errors,
            'errors' => 'The given data was invalid.',
        ], 422);
    }
}

if (!function_exists('responseCreate')) {
    function responseCreate(): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            [
                'message' => 'Add Data Successful!',
            ],
            200
        );
    }
}

if (!function_exists('responseUpdate')) {
    function responseUpdate(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => 'Update Data Successful',
        ], 200);
    }
}

if (!function_exists('responseDelete')) {
    function responseDelete(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'message' => 'Delete Data Successful',
        ], 200);
    }
}

if (!function_exists('responseIndex')) {
    function responseIndex(int $paging, mixed $data): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'totalPagination' => $paging,
            'data' => $data
        ], 200);
    }
}

if (!function_exists('responseList')) {
    function responseList(mixed $data): \Illuminate\Http\JsonResponse
    {
        return response()->json($data, 200);
    }
}

if (!function_exists('paginateData')) {
    function paginateData(mixed $query, \Illuminate\Http\Request $request): \Illuminate\Support\Collection
    {
        $itemPerPage = $request->rowPerPage;
        // dd($request->all());
        $totalPaging = 0;
        $count_data = 0;

        if ($itemPerPage == 0) {
            $data = $query->get();
            $count_data = $query->count();
        } else {
            $page = $request->goToPage;

            $offset = ($page - 1) * $itemPerPage;
            $count_data = $query->count();
            $count_result = $count_data - $offset;
            if ($count_result < 0) {
                $data = $query->offset(0)->limit($itemPerPage)->get();
            } else {
                $data = $query->offset($offset)->limit($itemPerPage)->get();
            }
            $totalPaging = $count_data / $itemPerPage;
        }


        return collect([
            'totalPagination' => ceil($totalPaging),
            'totalData' => $count_data,
            'data' => $data
        ]);
    }
}

if (!function_exists('responseSuccess')) {
    function responseSuccess(mixed $data = [], string $msg = 'Insert Data Successful!'): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            [
                'data' => $data,
                'message' => $msg,
            ],
            200
        );
    }
}

if (!function_exists('responseErrorValidation')) {
    function responseErrorValidation(mixed $errors, string $msg = 'The given data was invalid.'): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            [
                'message' => $msg,
                'errors' => $errors,
            ],
            422
        );
    }
}

if (!function_exists('responseError')) {
    function responseError(mixed $errors, string $msg = 'The given data was invalid.'): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            [
                'message' => $msg,
                'errors' => $errors,
            ],
            500
        );
    }
}

if (!function_exists('responseUnauthorize')) {
    function responseUnauthorize(string $errors = 'User Access not Authorize!', string $msg = 'The given data was invalid.'): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            [
                'message' => $msg,
                'errors' => [$errors],
            ],
            403
        );
    }
}

if (!function_exists('sendNotification')) {
    function sendNotification(int $userId, string $menuName, string $message, string $type = 'info'): void
    {
        \App\Models\PushNotification\PushNotification::create([
            'usersId'  => $userId,
            'menuName' => $menuName,
            'message'  => $message,
            'type'     => $type,
            'isRead'   => false,
        ]);

        try {
            broadcast(new \App\Events\MessageCreated($message, $type, $userId));
        } catch (\Exception $e) {
            // Pusher gagal tidak menghentikan proses utama
        }
    }
}

if (!function_exists('checkAccessIndex')) {
    function checkAccessIndex(string $identify, int $roleId): bool
    {
        $menuId = 0;

        $data = DB::table('grandChildrenMenuGroups as gc')
            ->select('gc.id', 'gc.identify')
            ->where('gc.identify', 'like', '%' . $identify . '%')
            ->first();

        if ($data) {
            $menuId = $data->id;
        }

        $res = true;

        $data = DB::table('accessControl as ac')
            ->where('ac.menuListId', '=', $menuId)
            ->where('ac.roleId', '=', $roleId)
            ->first();

        if ($data && $data->accessTypeId == 3) {
            $res = false;
        }

        return $res;
    }
}

if (!function_exists('checkAccessModify')) {
    function checkAccessModify(string $identify, int $roleId): bool
    {
        $menuId = 0;

        $data = DB::table('grandChildrenMenuGroups as gc')
            ->select('gc.id', 'gc.identify')
            ->where('gc.identify', 'like', '%' . $identify . '%')
            ->first();

        if ($data) {
            $menuId = $data->id;
        }

        $res = true;

        $data = DB::table('accessControl as ac')
            ->where('ac.menuListId', '=', $menuId)
            ->where('ac.roleId', '=', $roleId)
            ->first();

        if ($data && ($data->accessTypeId == 1 || $data->accessTypeId == 3)) {
            $res = false;
        }

        return $res;
    }
}

if (!function_exists('checkAccessDelete')) {
    function checkAccessDelete(string $identify, int $roleId): bool
    {
        $menuId = 0;

        $data = DB::table('grandChildrenMenuGroups as gc')
            ->select('gc.id', 'gc.identify')
            ->where('gc.identify', 'like', '%' . $identify . '%')
            ->first();

        if ($data) {
            $menuId = $data->id;
        }

        $res = true;

        $data = DB::table('accessControl as ac')
            ->where('ac.menuListId', '=', $menuId)
            ->where('ac.roleId', '=', $roleId)
            ->first();

        if ($data && ($data->accessTypeId == 1 || $data->accessTypeId == 2 || $data->accessTypeId == 3)) {
            $res = false;
        }

        return $res;
    }
}

function updateDiffStock(int $locationId, int $productId): void
{
    $productLoc = DB::table('productLocations')
        ->where('locationId', $locationId)
        ->where('productId', $productId)
        ->first();

    if ($productLoc) {
        $newDiffStock = $productLoc->inStock - $productLoc->lowStock;
        DB::table('productLocations')
            ->where('locationId', $locationId)
            ->where('productId', $productId)
            ->update(['diffStock' => $newDiffStock]);
    }
}

function recentActivity(int $userId, string $module, string $event, string $detail): void
{
    recentActivity::create([
        'userId' => $userId,
        'module' => $module,
        'event' => $event,
        'details' => $detail,
    ]);
}
