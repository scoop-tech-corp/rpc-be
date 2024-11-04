<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer\CustomerOccupation;
use App\Models\Customer\ReferenceCustomer;
use App\Models\Customer\TitleCustomer;
use App\Models\Customer\TypeIdCustomer;
use App\Models\CustomerGroups;
use App\Models\Location;
use DB;

class ImportCustomerController extends Controller
{
    function index(Request $request)
    {
        $itemPerPage = $request->rowPerPage;

        $page = $request->goToPage;

        $data = DB::table('importCustomers as tc')
            ->join('users as u', 'tc.userId', 'u.id')
            ->select(
                'tc.id',
                'tc.fileName',
                'tc.totalData',
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(tc.created_at, '%d/%m/%Y %H:%i:%s') as createdAt")
            )
            ->where('tc.isDeleted', '=', 0);

        if ($request->search) {
            $res = $this->Search($request);
            if ($res) {
                $data = $data->where($res[0], 'like', '%' . $request->search . '%');

                for ($i = 1; $i < count($res); $i++) {
                    $data = $data->orWhere($res[$i], 'like', '%' . $request->search . '%');
                }
            } else {
                $data = [];
                return response()->json([
                    'totalPagination' => 0,
                    'data' => $data
                ], 200);
            }
        }

        if ($request->orderValue) {
            $data = $data->orderBy($request->orderColumn, $request->orderValue);
        }

        $data = $data->orderBy('tc.updated_at', 'desc');

        $offset = ($page - 1) * $itemPerPage;

        $count_data = $data->count();
        $count_result = $count_data - $offset;

        if ($count_result < 0) {
            $data = $data->offset(0)->limit($itemPerPage)->get();
        } else {
            $data = $data->offset($offset)->limit($itemPerPage)->get();
        }

        $totalPaging = $count_data / $itemPerPage;

        return response()->json([
            'totalPagination' => ceil($totalPaging),
            'data' => $data
        ], 200);
    }

    private function Search($request)
    {

        $temp_column = null;

        $data = DB::table('importCustomers as tc')
            ->join('users as u', 'tc.userId', 'u.id')
            ->select(
                'tc.fileName',
            )
            ->where('tc.isDeleted', '=', 0);

        if ($request->search) {
            $data = $data->where('tc.fileName', 'like', '%' . $request->search . '%');
        }

        $data = $data->get();

        if (count($data)) {
            $temp_column[] = 'tc.fileName';
        }

        return $temp_column;
    }

    function import(Request $request)
    {
        // DB::beginTransaction();
        // try {

        $validate = Validator::make($request->all(), [
            'file' => 'required|mimes:xls,xlsx',
        ]);

        if ($validate->fails()) {
            $errors = $validate->errors()->all();

            return response()->json([
                'errors' => 'The given data was invalid.',
                'message' => $errors,
            ], 422);
        }

        $id = $request->user()->id;

        $rows = Excel::toArray(new ImportCustomer($id), $request->file('file'));
        $src1 = $rows[0];
        $src2 = $rows[1];
        $src3 = $rows[2];
        $src4 = $rows[3];
        $src5 = $rows[4];
        $src6 = $rows[5];

        $count_row = 1;
        $total_data = 0;

        if (count($src1) > 2) {
            foreach ($src1 as $value) {

                if ($value['ID'] == null && $value['nomor_member'] == null && $value['nama_depan'] == null) {
                    break;
                }

                if ($value['nama_depan'] == "Wajib Diisi") {
                    $count_row += 2;
                    continue;
                }

                if ($value['id'] == "") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any empty cell on column Id at row ' . $count_row],
                    ], 422);
                }

                if ($value['nama_depan'] == "") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any empty cell on column Nama Depan at row ' . $count_row],
                    ], 422);
                }

                if ($value['jenis_kelamin'] == "") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any empty cell on column Jenis Kelamin at row ' . $count_row],
                    ], 422);
                }

                if ($value['jenis_kelamin'] != "P" && $value['jenis_kelamin'] != "W") {

                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any invalid input on column Jenis Kelamin at row ' . $count_row],
                    ], 422);
                }

                if ($value['id_gelar'] == "") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any empty cell on column ID Gelar at row ' . $count_row],
                    ], 422);
                }

                $title = TitleCustomer::where('id', '=', $value['id_gelar'])->first();

                if (!$title) {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is no any Gelar on system at row ' . $count_row],
                    ], 422);
                }

                if ($value['id_group_pelanggan'] == "") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any empty cell on column ID Grup Pelanggan at row ' . $count_row],
                    ], 422);
                }

                $customer_groups = CustomerGroups::where('id', '=', $value['id_group_pelanggan'])->first();

                if (!$customer_groups) {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is no any Grup Pelanggan on system at row ' . $count_row],
                    ], 422);
                }

                if ($value['id_lokasi'] == "") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any empty cell on column ID Lokasi at row ' . $count_row],
                    ], 422);
                }

                $loc = Location::where('id', '=', $value['id_lokasi'])->first();

                if (!$loc) {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is no any ID Lokasi on system at row ' . $count_row],
                    ], 422);
                }

                if (!$this->isValidDate($value['tanggal_join'])) {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is invalid date format Tanggal Join at row ' . $count_row],
                    ], 422);
                }

                if ($value['id_tipe_identitas'] == "") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any empty cell on column ID Tipe Identitas at row ' . $count_row],
                    ], 422);
                }

                $typeId = TypeIdCustomer::where('id', '=', $value['id_tipe_identitas'])->first();

                if (!$typeId) {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is no any ID Tipe Identitas on system at row ' . $count_row],
                    ], 422);
                }

                if ($value['nomor_kartu_identitas'] == "") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any empty cell on column Nomor Kartu Identitas at row ' . $count_row],
                    ], 422);
                }

                if ($value['id_pekerjaan'] == "") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any empty cell on column ID Pekerjaan at row ' . $count_row],
                    ], 422);
                }

                $custOccupation = CustomerOccupation::where('id', '=', $value['id_pekerjaan'])->first();

                if (!$custOccupation) {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is no any ID Pekerjaan on system at row ' . $count_row],
                    ], 422);
                }

                if (!$this->isValidDate($value['tanggal_lahir'])) {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is invalid date format Tanggal Lahir at row ' . $count_row],
                    ], 422);
                }

                if ($value['id_referensi'] == "") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any empty cell on column ID Referensi at row ' . $count_row],
                    ], 422);
                }

                $ref = ReferenceCustomer::where('id', '=', $value['id_referensi'])->first();

                if (!$ref) {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is no any ID Referensi on system at row ' . $count_row],
                    ], 422);
                }

                if ($value['pengingat_booking'] != "0" && $value['pengingat_booking'] != "1") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any invalid input on column Pengingat Booking at row ' . $count_row],
                    ], 422);
                }

                if ($value['pengingat_pembayaran'] != "0" && $value['pengingat_pembayaran'] != "1") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any invalid input on column Pengingat Pembayaran at row ' . $count_row],
                    ], 422);
                }

                $total_data += 1;
                $count_row += 1;
            }
        }

        $count_row = 1;
        $total_data = 0;

        if (count($src2) > 2) {
            foreach ($src2 as $value) {
                if ($value['id'] == "Wajib diisi berdasarkan ID di sheet Detail") {
                    $count_row += 2;
                    continue;
                }

                $total_data += 1;
                $count_row += 1;
            }
        }

        $count_row = 1;
        $total_data = 0;
        //     DB::commit();

        //     return responseSuccess($userId, 'Insert Data Successful!');
        // } catch (\Throwable $th) {
        //     DB::rollback();

        //     return response()->json([
        //         'message' => 'Failed',
        //         'errors' => $th,
        //     ]);
        // }


    }

    //     public function findById(Request $request)
// {
//     $data = [
//         [
//             "id" => 25,
//             "name" => "Promo Hemat",
//             "type" => "Bundle",
//             "startDate" => "20/01/2024",
//             "endDate" => "22/01/2024",
//             "status" => "Active",
//             "createdBy" => "Danny",
//             "createdAt" => "17/03/2024 14:27:01"
//         ],
//         [
//             "id" => 21,
//             "name" => "Promo Hemat",
//             "type" => "Based Sales",
//             "startDate" => "20/01/2024",
//             "endDate" => "22/01/2024",
//             "status" => "Active",
//             "createdBy" => "Danny",
//             "createdAt" => "18/02/2024 16:08:42"
//         ],
//         [
//             "id" => 20,
//             "name" => "Promo Hemat",
//             "type" => "Bundle",
//             "startDate" => "20/01/2024",
//             "endDate" => "22/01/2024",
//             "status" => "Active",
//             "createdBy" => "Danny",
//             "createdAt" => "18/02/2024 16:07:41"
//         ],
//         [
//             "id" => 16,
//             "name" => "Promo Hemat",
//             "type" => "Bundle",
//             "startDate" => "20/01/2024",
//             "endDate" => "22/01/2024",
//             "status" => "Active",
//             "createdBy" => "Danny",
//             "createdAt" => "18/02/2024 16:02:54"
//         ],
//         [
//             "id" => 16,
//             "name" => "Promo Hemat",
//             "type" => "Discount",
//             "startDate" => "20/01/2024",
//             "endDate" => "22/01/2024",
//             "status" => "Active",
//             "createdBy" => "Danny",
//             "createdAt" => "18/02/2024 12:44:29"
//         ]
//     ];

//     // Use filter to find all entries with ID 16
//     $results = collect($data)->where('id', 16);

//     if ($results->isNotEmpty()) {
//         return response()->json($results->values()->all());
//     } else {
//         return response()->json(['error' => 'ID not found'], 404);
//     }
// }

    private function isValidDate($date)
    {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
