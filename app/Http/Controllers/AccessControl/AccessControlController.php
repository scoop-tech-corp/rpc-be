<?php

namespace App\Http\Controllers\AccessControl;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AccessControl\AccessControlHistory;
use App\Models\AccessControl\AccessControl;
use DB;

class AccessControlController extends Controller
{
    public function index(Request $request)
    {

        try {

            $defaultRowPerPage = 5;
            $defaultOrderBy = "asc";

            $data = DB::table('users as a')
                ->leftjoin('usersRoles as b', 'b.id', '=', 'a.roleId')
                ->leftjoin('jobTitle as c', 'c.id', '=', 'a.jobTitleId')
                ->select(
                    'a.id as id',
                    DB::raw("CONCAT(IFNULL(a.firstName,''), case when a.middleName is null then '' else ' ' end , IFNULL(a.middleName,'') ,case when a.lastName is null then '' else ' ' end, case when a.lastName is null then '' else a.lastName end ) as name"),
                    'b.roleName as roleName',
                    'c.jobName as jobName',
                    'a.createdBy as createdBy',
                    DB::raw('a.created_at as createdAt'),
                    'a.updated_at'
                )
                ->where([
                    ['a.isDeleted', '=', '0'],
                    ['b.isActive', '=', '1'],
                    ['c.isActive', '=', '1'],
                ]);


            $data = DB::table($data)
                ->select(
                    'id',
                    'name',
                    'roleName',
                    'jobName',
                    'createdBy',
                    'createdAt',
                    'updated_at'
                );

            if ($request->search) {

                $res = $this->Search($request);

                if ($res) {

                    if ($res == "name") {

                        $data = $data->where('name', 'like', '%' . $request->search . '%');
                    } else if ($res == "roleName") {

                        $data = $data->where('roleName', 'like', '%' . $request->search . '%');
                    } else if ($res == "jobName") {

                        $data = $data->where('jobName', 'like', '%' . $request->search . '%');
                    } else if ($res == "createdBy") {

                        $data = $data->where('createdBy', 'like', '%' . $request->search . '%');
                    } else if ($res == "createdAt") {

                        $data = $data->where('createdAt', 'like', '%' . $request->search . '%');
                    } else {

                        $data = [];
                        return response()->json([
                            'totalPagination' => 0,
                            'data' => $data
                        ], 200);
                    }
                }
            }


            if ($request->orderValue) {

                $defaultOrderBy = $request->orderValue;
            }


            $checkOrder = null;
            if ($request->orderColumn && $defaultOrderBy) {

                $listOrder = array(
                    'id',
                    'name',
                    'roleName',
                    'jobName',
                    'createdBy',
                    'createdAt',
                );

                if (!in_array($request->orderColumn, $listOrder)) {

                    return response()->json([
                        'message' => 'failed',
                        'errors' => 'Please try different order column',
                        'orderColumn' => $listOrder,
                    ]);
                }

                if (strtolower($defaultOrderBy) != "asc" && strtolower($defaultOrderBy) != "desc") {
                    return response()->json([
                        'message' => 'failed',
                        'errors' => 'order value must Ascending: ASC or Descending: DESC ',
                    ]);
                }


                $checkOrder = true;
            }


            if ($checkOrder) {

                $data = DB::table($data)
                    ->select(
                        'id',
                        'name',
                        'roleName',
                        'jobName',
                        'createdBy',
                        'createdAt'
                    )
                    ->orderBy($request->orderColumn, $defaultOrderBy)
                    ->orderBy('updated_at', 'desc');
            } else {


                $data = DB::table($data)
                    ->select(
                        'id',
                        'name',
                        'roleName',
                        'jobName',
                        'createdBy',
                        'createdAt'
                    )
                    ->orderBy('updated_at', 'desc');
            }


            if ($request->rowPerPage > 0) {
                $defaultRowPerPage = $request->rowPerPage;
            }

            $goToPage = $request->goToPage;

            $offset = ($goToPage - 1) * $defaultRowPerPage;

            $count_data = $data->count();
            $count_result = $count_data - $offset;

            if ($count_result < 0) {
                $data = $data->offset(0)->limit($defaultRowPerPage)->get();
            } else {
                $data = $data->offset($offset)->limit($defaultRowPerPage)->get();
            }

            $total_paging = $count_data / $defaultRowPerPage;

            return response()->json(['totalPagination' => ceil($total_paging), 'data' => $data], 200);
        } catch (Exception $e) {

            return response()->json([
                'message' => 'Failed',
                'errors' => $e,
            ], 422);
        }
    }









    public function indexHistory(Request $request)
    {

        try {

            $defaultRowPerPage = 5;
            $defaultOrderBy = "asc";

            $data = DB::table('accesscontrol as a')
                ->leftjoin('usersroles as b', 'b.id', '=', 'a.roleId')
                ->leftjoin('menuList as c', 'c.id', '=', 'a.menuListId')
                ->select(
                    'a.id as id',
                    'c.menuName as menuName',
                    'b.roleName as roleName',
                    DB::raw('a.created_at as createdAt'),
                    'a.updated_at'
                )
                ->where([
                    ['b.isActive', '=', '1'],
                    ['c.isActive', '=', '1'],
                ]);


            // $data = DB::table($data)
            //     ->select(
            //         'id',
            //         'name',
            //         'roleName',
            //         'jobName',
            //         'createdBy',
            //         'createdAt',
            //         'updated_at'
            //     );

            // if ($request->search) {

            //     $res = $this->Search($request);

            //     if ($res) {

            //         if ($res == "name") {

            //             $data = $data->where('name', 'like', '%' . $request->search . '%');
            //         } else if ($res == "roleName") {

            //             $data = $data->where('roleName', 'like', '%' . $request->search . '%');
            //         } else if ($res == "jobName") {

            //             $data = $data->where('jobName', 'like', '%' . $request->search . '%');
            //         } else if ($res == "createdBy") {

            //             $data = $data->where('createdBy', 'like', '%' . $request->search . '%');
            //         } else if ($res == "createdAt") {

            //             $data = $data->where('createdAt', 'like', '%' . $request->search . '%');
            //         } else {

            //             $data = [];
            //             return response()->json([
            //                 'totalPagination' => 0,
            //                 'data' => $data
            //             ], 200);
            //         }
            //     }
            // }


            // if ($request->orderValue) {

            //     $defaultOrderBy = $request->orderValue;
            // }


            // $checkOrder = null;
            // if ($request->orderColumn && $defaultOrderBy) {

            //     $listOrder = array(
            //         'id',
            //         'name',
            //         'roleName',
            //         'jobName',
            //         'createdBy',
            //         'createdAt',
            //     );

            //     if (!in_array($request->orderColumn, $listOrder)) {

            //         return response()->json([
            //             'message' => 'failed',
            //             'errors' => 'Please try different order column',
            //             'orderColumn' => $listOrder,
            //         ]);
            //     }

            //     if (strtolower($defaultOrderBy) != "asc" && strtolower($defaultOrderBy) != "desc") {
            //         return response()->json([
            //             'message' => 'failed',
            //             'errors' => 'order value must Ascending: ASC or Descending: DESC ',
            //         ]);
            //     }


            //     $checkOrder = true;
            // }


            // if ($checkOrder) {

            //     $data = DB::table($data)
            //         ->select(
            //             'id',
            //             'name',
            //             'roleName',
            //             'jobName',
            //             'createdBy',
            //             'createdAt'
            //         )
            //         ->orderBy($request->orderColumn, $defaultOrderBy)
            //         ->orderBy('updated_at', 'desc');
            // } else {


            //     $data = DB::table($data)
            //         ->select(
            //             'id',
            //             'name',
            //             'roleName',
            //             'jobName',
            //             'createdBy',
            //             'createdAt'
            //         )
            //         ->orderBy('updated_at', 'desc');
            // }


            if ($request->rowPerPage > 0) {
                $defaultRowPerPage = $request->rowPerPage;
            }

            $goToPage = $request->goToPage;

            $offset = ($goToPage - 1) * $defaultRowPerPage;

            $count_data = $data->count();
            $count_result = $count_data - $offset;

            if ($count_result < 0) {
                $data = $data->offset(0)->limit($defaultRowPerPage)->get();
            } else {
                $data = $data->offset($offset)->limit($defaultRowPerPage)->get();
            }

            $total_paging = $count_data / $defaultRowPerPage;

            return response()->json(['totalPagination' => ceil($total_paging), 'data' => $data], 200);
            
        } catch (Exception $e) {

            return response()->json([
                'message' => 'Failed',
                'errors' => $e,
            ], 422);
        }
    }









    private function Search($request)
    {


        $data = DB::table('users as a')
            ->leftjoin('usersRoles as b', 'b.id', '=', 'a.roleId')
            ->leftjoin('jobTitle as c', 'c.id', '=', 'a.jobTitleId')
            ->select(
                'a.id as id',
                DB::raw("CONCAT(IFNULL(a.firstName,''), case when a.middleName is null then '' else ' ' end , IFNULL(a.middleName,'') ,case when a.lastName is null then '' else ' ' end, case when a.lastName is null then '' else a.lastName end ) as name"),
                'b.roleName as roleName',
                'c.jobName as jobName',
                'a.createdBy as createdBy',
                DB::raw('a.created_at as createdAt'),
                'a.updated_at'
            )
            ->where([
                ['a.isDeleted', '=', '0'],
                ['b.isActive', '=', '1'],
                ['c.isActive', '=', '1'],
            ]);

        $data = DB::table($data)
            ->select(
                'id',
                'name',
                'roleName',
                'jobName',
                'createdBy',
                'createdAt',
                'updated_at'
            );

        if ($request->search) {

            $data = $data->where('name', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'name';
            return $temp_column;
        }




        $data = DB::table('users as a')
            ->leftjoin('usersRoles as b', 'b.id', '=', 'a.roleId')
            ->leftjoin('jobTitle as c', 'c.id', '=', 'a.jobTitleId')
            ->select(
                'a.id as id',
                DB::raw("CONCAT(IFNULL(a.firstName,''), case when a.middleName is null then '' else ' ' end , IFNULL(a.middleName,'') ,case when a.lastName is null then '' else ' ' end, case when a.lastName is null then '' else a.lastName end ) as name"),
                'b.roleName as roleName',
                'c.jobName as jobName',
                'a.createdBy as createdBy',
                DB::raw('a.created_at as createdAt'),
                'a.updated_at'
            )
            ->where([
                ['a.isDeleted', '=', '0'],
                ['b.isActive', '=', '1'],
                ['c.isActive', '=', '1'],
            ]);

        $data = DB::table($data)
            ->select(
                'id',
                'name',
                'roleName',
                'jobName',
                'createdBy',
                'createdAt',
                'updated_at'
            );

        if ($request->search) {

            $data = $data->where('roleName', 'like', '%' . $request->search . '%');
        }


        $data = $data->get();

        if (count($data)) {
            $temp_column = 'roleName';
            return $temp_column;
        }



        $data = DB::table('users as a')
            ->leftjoin('usersRoles as b', 'b.id', '=', 'a.roleId')
            ->leftjoin('jobTitle as c', 'c.id', '=', 'a.jobTitleId')
            ->select(
                'a.id as id',
                DB::raw("CONCAT(IFNULL(a.firstName,''), case when a.middleName is null then '' else ' ' end , IFNULL(a.middleName,'') ,case when a.lastName is null then '' else ' ' end, case when a.lastName is null then '' else a.lastName end ) as name"),
                'b.roleName as roleName',
                'c.jobName as jobName',
                'a.createdBy as createdBy',
                DB::raw('a.created_at as createdAt'),
                'a.updated_at'
            )
            ->where([
                ['a.isDeleted', '=', '0'],
                ['b.isActive', '=', '1'],
                ['c.isActive', '=', '1'],
            ]);

        $data = DB::table($data)
            ->select(
                'id',
                'name',
                'roleName',
                'jobName',
                'createdBy',
                'createdAt',
                'updated_at'
            );

        if ($request->search) {

            $data = $data->where('jobName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column = 'jobName';
            return $temp_column;
        }


        $data = DB::table('users as a')
            ->leftjoin('usersRoles as b', 'b.id', '=', 'a.roleId')
            ->leftjoin('jobTitle as c', 'c.id', '=', 'a.jobTitleId')
            ->select(
                'a.id as id',
                DB::raw("CONCAT(IFNULL(a.firstName,''), case when a.middleName is null then '' else ' ' end , IFNULL(a.middleName,'') ,case when a.lastName is null then '' else ' ' end, case when a.lastName is null then '' else a.lastName end ) as name"),
                'b.roleName as roleName',
                'c.jobName as jobName',
                'a.createdBy as createdBy',
                DB::raw('a.created_at as createdAt'),
                'a.updated_at'
            )
            ->where([
                ['a.isDeleted', '=', '0'],
                ['b.isActive', '=', '1'],
                ['c.isActive', '=', '1'],
            ]);

        $data = DB::table($data)
            ->select(
                'id',
                'name',
                'roleName',
                'jobName',
                'createdBy',
                'createdAt',
                'updated_at'
            );

        if ($request->search) {

            $data = $data->where('createdBy', 'like', '%' . $request->search . '%');
        }


        $data = $data->get();

        if (count($data)) {
            $temp_column = 'createdBy';
            return $temp_column;
        }


        $data = DB::table('users as a')
            ->leftjoin('usersRoles as b', 'b.id', '=', 'a.roleId')
            ->leftjoin('jobTitle as c', 'c.id', '=', 'a.jobTitleId')
            ->select(
                'a.id as id',
                DB::raw("CONCAT(IFNULL(a.firstName,''), case when a.middleName is null then '' else ' ' end , IFNULL(a.middleName,'') ,case when a.lastName is null then '' else ' ' end, case when a.lastName is null then '' else a.lastName end ) as name"),
                'b.roleName as roleName',
                'c.jobName as jobName',
                'a.createdBy as createdBy',
                DB::raw('a.created_at as createdAt'),
                'a.updated_at'
            )
            ->where([
                ['a.isDeleted', '=', '0'],
                ['b.isActive', '=', '1'],
                ['c.isActive', '=', '1'],
            ]);

        $data = DB::table($data)
            ->select(
                'id',
                'name',
                'roleName',
                'jobName',
                'createdBy',
                'createdAt',
                'updated_at'
            );

        if ($request->search) {

            $data = $data->where('createdAt', 'like', '%' . $request->search . '%');
        }



        $data = $data->get();

        if (count($data)) {
            $temp_column = 'createdAt';
            return $temp_column;
        }
    }
}
