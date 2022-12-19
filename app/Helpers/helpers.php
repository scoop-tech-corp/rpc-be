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
            ->join('users_role as ur', 'ur.id', 'u.role')
            ->select('u.id','ur.roleName')
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
            ->join('users_role as ur', 'ur.id', 'u.role')
            ->select('u.id','ur.roleName')
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
            ->join('users_role as ur', 'ur.id', 'u.role')
            ->select('u.id','ur.roleName')
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
            ->join('users_role as ur', 'ur.id', 'u.role')
            ->select('u.id','ur.roleName')
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
            ->join('users_role as ur', 'ur.id', 'u.role')
            ->select('u.id','ur.roleName')
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
            ->join('users_role as ur', 'ur.id', 'u.role')
            ->select('u.id','ur.roleName')
            ->where('u.id', '=', $id)
            ->first();

        return $user->roleName;
    }
}
