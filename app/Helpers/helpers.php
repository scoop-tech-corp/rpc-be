<?php

/**
 * Write code on Method
 *
 * @return response()
 */

use App\Models\ProductClinicLog;
use App\Models\productRestockLog;
use App\Models\ProductTransferLog;
use App\Models\ProductSellLog;

if (!function_exists('adminAccess')) {
    function adminAccess($id)
    {

        $user = DB::table('users as u')
            ->leftjoin('usersRoles as ur', 'ur.id', 'u.roleId')
            ->select('u.id', 'ur.roleName')
            ->where('u.id', '=', $id)
            ->first();

        if ($user->roleName != "Administrator") {
            return false;
        } else {
            return true;
        }
    }
}

if (!function_exists('officeAccess')) {
    function officeAccess($id)
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
    function managerAccess($id)
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
    function staffAccess($id)
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
    function customerAccess($id)
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
    function intershipAccess($id)
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
    function role($id)
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
    function roleStaffLeave($id)
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
    function convertTrueFalse($value)
    {
        if ($value == 'true' || $value == 'TRUE') {
            return 1;
        } elseif ($value == 'false' || $value == 'FALSE') {
            return 0;
        }
    }
}

if (!function_exists('productSellLog')) {
    function productSellLog($productId, $transaction, $remark, $quantity, $balance, $userId)
    {
        ProductSellLog::create([
            'productSellId' => $productId,
            'transaction' => $transaction,
            'remark' => $remark,
            'quantity' => $quantity,
            'balance' => $balance,
            'userId' => $userId,
        ]);
    }
}

if (!function_exists('productClinicLog')) {
    function productClinicLog($productId, $transaction, $remark, $quantity, $balance, $userId)
    {
        ProductClinicLog::create([
            'productClinicId' => $productId,
            'transaction' => $transaction,
            'remark' => $remark,
            'quantity' => $quantity,
            'balance' => $balance,
            'userId' => $userId,
        ]);
    }
}

if (!function_exists('productRestockLog')) {
    function productRestockLog($productRestockId, $event, $detail, $userId)
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
    function productTransferLog($productTransferId, $event, $detail, $userId)
    {
        productTransferLog::create([
            'productTransferId' => $productTransferId,
            'event' => $event,
            'details' => $detail,
            'userId' => $userId,
        ]);
    }
}

if (!function_exists('responseInvalid')) {
    function responseInvalid($errors)
    {
        return response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $errors,
        ], 422);
    }
}

if (!function_exists('responseCreate')) {
    function responseCreate()
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
    function responseUpdate()
    {
        return response()->json([
            'message' => 'Update Data Successful',
        ], 200);
    }
}

if (!function_exists('responseDelete')) {
    function responseDelete()
    {
        return response()->json([
            'message' => 'Delete Data Successful',
        ], 200);
    }
}

if (!function_exists('responseIndex')) {
    function responseIndex($paging, $data)
    {
        return response()->json([
            'totalPagination' => $paging,
            'data' => $data
        ], 200);
    }
}

if (!function_exists('responseList')) {
    function responseList($data)
    {
        return response()->json($data, 200);
    }
}

if(!function_exists('paginateData')){
    function paginateData($query,  $request)
    {
        $itemPerPage = $request->rowPerPage;
        $totalPaging = 0;
        $count_data = 0;
    
        if($itemPerPage == 0){
            $data = $query->get();
            $count_data = $query->count();
        }else{
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
if(!function_exists('responseSuccess')){
    function responseSuccess($data=[], $msg='Insert Data Successful!'){
        return response()->json(
            [
                'data' => $data,
                'message' => $msg,
            ],
            200
        );
    }
}
if(!function_exists('responseErrorValidation')){
    function responseErrorValidation($errors, $msg='The given data was invalid.'){
        return response()->json(
            [
                'message' => $msg,
                'errors' => $errors,
            ],
            422
        );
    }
}
if(!function_exists('responseError')){
    function responseError($errors, $msg='The given data was invalid.'){
        return response()->json(
            [
                'message' => $msg,
                'errors' => $errors,
            ],
            500
        );
    }
}

//add by danny wahyudi
// if (!function_exists('securityGroupAdmin')) {
//     function securityGroupAdmin($id)
//     {
//         $user = DB::table('users as u')
//             ->select('u.securityGroupAdmin')
//             ->where('u.id', '=', $id)
//             ->first();

//         return $user->securityGroupAdmin;
//     }
// }

// if (!function_exists('securityGroupManager')) {
//     function securityGroupManager($id)
//     {
//         $user = DB::table('users as u')
//             ->select('u.securityGroupManager')
//             ->where('u.id', '=', $id)
//             ->first();

//         return $user->securityGroupManager;
//     }
// }

// if (!function_exists('securityGroupVet')) {
//     function securityGroupVet($id)
//     {
//         $user = DB::table('users as u')
//             ->select('u.securityGroupVet')
//             ->where('u.id', '=', $id)
//             ->first();

//         return $user->securityGroupVet;
//     }
// }


// if (!function_exists('securityGroupReceptionist')) {
//     function securityGroupReceptionist($id)
//     {
//         $user = DB::table('users as u')
//             ->select('u.securityGroupReceptionist')
//             ->where('u.id', '=', $id)
//             ->first();

//         return $user->securityGroupReceptionist;
//     }
// }
// //end add by danny wahyudi
