<?php

/**
 * Write code on Method
 *
 * @return response()
 */
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

if(!function_exists('convertTrueFalse')){
    function convertTrueFalse($value)
    {
        if($value == 'true' || $value =='TRUE'){
            return 1;
        }
        elseif ($value == 'false'|| $value == 'FALSE') {
            return 0;
        }
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