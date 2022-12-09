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
            ->where('u.id', '=', $id)
            ->first();

        if ($user != "admin") {
            return false;
        } else {
            return true;
        }
    }
}
