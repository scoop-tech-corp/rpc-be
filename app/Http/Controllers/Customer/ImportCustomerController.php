<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Imports\Customer\ImportCustomer;
use Illuminate\Http\Request;
use App\Models\Customer\CustomerOccupation;
use App\Models\Customer\PetCategory;
use App\Models\Customer\ReferenceCustomer;
use App\Models\Customer\TitleCustomer;
use App\Models\Customer\TypeIdCustomer;
use App\Models\CustomerGroups;
use App\Models\ImportCustomer as ModelsImportCustomer;
use App\Models\Location;
use DB;
use Validator;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

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
        $validate = Validator::make($request->all(), [
            'file' => 'required|mimes:xls,xlsx',
        ]);

        $file = $request->file('file');
        $filename = $file->getClientOriginalName();

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

                if ($value['nomor_member'] == "") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any empty cell on column Nomor Member at row ' . $count_row],
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

                if ($value['tanggal_join'] == "") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any empty cell on column Tanggal Join at row ' . $count_row],
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

                if ($value['id'] == null && $value['nama_vet'] == null && $value['jenis_vet'] == null) {
                    break;
                }

                if ($value['nama_vet'] == "") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any empty cell on column Nama Vet at row ' . $count_row],
                    ], 422);
                }

                if ($value['jenis_vet'] == "") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any empty cell on column Jenis Vet at row ' . $count_row],
                    ], 422);
                }

                $petCat = PetCategory::where('id', '=', $value['jenis_vet'])->first();

                if (!$petCat) {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is no any ID Jenis Vet on system at row ' . $count_row],
                    ], 422);
                }

                if ($value['ras'] == "") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any empty cell on column Ras at row ' . $count_row],
                    ], 422);
                }

                if ($value['kondisi'] == "") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any empty cell on column Kondisi at row ' . $count_row],
                    ], 422);
                }

                if ($value['jenis_kelamin'] == "") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any empty cell on column Jenis Kelamin at Sheet Vet, at row ' . $count_row],
                    ], 422);
                }

                if ($value['jenis_kelamin'] != "J" && $value['jenis_kelamin'] != "B") {

                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any invalid input on column Jenis Kelamin at Sheet Vet at row ' . $count_row],
                    ], 422);
                }

                if ($value['sudah_steril'] == "") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any empty cell on column Sudah Steril at row ' . $count_row],
                    ], 422);
                }

                if ($value['sudah_steril'] != "0" && $value['sudah_steril'] != "1") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any invalid input on column Sudah Steril at row ' . $count_row],
                    ], 422);
                }

                if ($value['tanggal_lahir'] == "" && $value['bulan'] == "" && $value['tahun'] == "") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any empty cell on column Tanggal Lahir, Bulan, and Tahun at row ' . $count_row],
                    ], 422);
                }



                if (!empty($value['tanggal_lahir'])) {

                    $checkSerial = $this->isExcelSerialDate($value['tanggal_lahir']);
                    $status = false;

                    if (!$this->isValidDate($value['tanggal_lahir'])) {
                        $status = true;
                    } elseif ($checkSerial) {
                        $status = true;
                    }

                    if (!$status) {

                        return response()->json([
                            'errors' => 'The given data was invalid.',
                            'message' => ['There is invalid date format Tanggal Lahir on sheet Vet at row ' . $count_row],
                        ], 422);
                    }
                }

                if ($value['bulan'] != "") {
                    if (!is_numeric($value['bulan']) || $value['bulan'] < 1 || $value['bulan'] > 12) {

                        //return $value['bulan'];
                        return response()->json([
                            'errors' => 'The given data was invalid.',
                            'message' => ['Column Bulan Should be number and between 1 and 12 at row ' . $count_row],
                        ], 422);
                    }
                }

                if ($value['tahun'] != "") {
                    if (!is_numeric($value['tahun']) || (int)$value['tahun'] < 1000 || (int)$value['tahun'] > 9999) {
                        return response()->json([
                            'errors' => 'The given data was invalid.',
                            'message' => ['Column Tahun Should be number and between 1000 and 9999 at row ' . $count_row],
                        ], 422);
                    }
                }

                if ($value['warna'] == "") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any empty cell on column Warna at row ' . $count_row],
                    ], 422);
                }

                $total_data += 1;
                $count_row += 1;
            }
        }

        $count_row = 1;
        $total_data = 0;

        if (count($src3) > 2) {

            foreach ($src3 as $value) {

                if ($value['id'] == "Wajib diisi berdasarkan ID di sheet Detail") {
                    $count_row += 2;
                    continue;
                }

                if ($value['alamat_jalan'] == "") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any empty cell on column Alamat Jalan at row ' . $count_row],
                    ], 422);
                }

                if ($value['jadikan_sebagai_alamat_utama'] != "0" && $value['jadikan_sebagai_alamat_utama'] != "1") {
                    return response()->json([
                        'errors' => 'The given data was invalid.',
                        'message' => ['There is any invalid input on column Jadikan Sebagai Alamat Utama at row ' . $count_row],
                    ], 422);
                }

                $total_data += 1;
                $count_row += 1;
            }
        }


        $count_row = 1;
        $total_data = 0;

        //telpon
        if (count($src4) > 2) {

            foreach ($src4 as $value) {

                if ($value['id'] == "Wajib diisi berdasarkan ID di sheet Detail") {
                    $count_row += 2;
                    continue;
                }

                if ($value['id_pemakaian'] != "") {

                    $usageTelp = DataStaticCustomers::where('id', '=', $value['id_pemakaian'])
                        ->where([
                            ['isDeleted', '=', '0'],
                            ['value', '=', 'Usage']
                        ])->first();

                    if (!$usageTelp) {
                        return response()->json([
                            'errors' => 'The given data was invalid.',
                            'message' => ['There is no any ID Jenis Vet on system at row ' . $count_row],
                        ], 422);
                    }
                }

                if ($value['id_tipe'] != "") {

                    $usageTelp = DataStaticCustomers::where('id', '=', $value['id_tipe'])
                        ->where([
                            ['isDeleted', '=', '0'],
                            ['value', '=', 'Telephone']
                        ])->first();

                    if (!$usageTelp) {
                        return response()->json([
                            'errors' => 'The given data was invalid.',
                            'message' => ['There is no any ID Jenis Vet on system at row ' . $count_row],
                        ], 422);
                    }
                }


                $total_data += 1;
                $count_row += 1;
            }
        }


        $count_row = 1;
        $total_data = 0;

        //email
        if (count($src5) > 2) {

            foreach ($src5 as $value) {

                if ($value['id'] == "Wajib diisi berdasarkan ID di sheet Detail") {
                    $count_row += 2;
                    continue;
                }

                if ($value['id_pemakaian'] != "") {

                    $usageEmail = DataStaticCustomers::where('id', '=', $value['id_pemakaian'])
                        ->where([
                            ['isDeleted', '=', '0'],
                            ['value', '=', 'Usage']
                        ])->first();

                    if (!$usageEmail) {
                        return response()->json([
                            'errors' => 'The given data was invalid.',
                            'message' => ['There is no any ID Jenis Vet on system at row ' . $count_row],
                        ], 422);
                    }
                }

                $total_data += 1;
                $count_row += 1;
            }
        }

        $count_row = 1;
        $total_data = 0;

        //messenger
        if (count($src6) > 2) {

            foreach ($src6 as $value) {

                if ($value['id'] == "Wajib diisi berdasarkan ID di sheet Detail") {
                    $count_row += 2;
                    continue;
                }

                if ($value['id_pemakaian'] != "") {

                    $usageMes = DataStaticCustomers::where('id', '=', $value['id_pemakaian'])
                        ->where([
                            ['isDeleted', '=', '0'],
                            ['value', '=', 'Usage']
                        ])->first();

                    if (!$usageMes) {
                        return response()->json([
                            'errors' => 'The given data was invalid.',
                            'message' => ['There is no any ID Jenis Vet on system at row ' . $count_row],
                        ], 422);
                    }
                }

                if ($value['id_tipe'] != "") {

                    $usageMes = DataStaticCustomers::where('id', '=', $value['id_tipe'])
                        ->where([
                            ['isDeleted', '=', '0'],
                            ['value', '=', 'Messenger']
                        ])->first();

                    if (!$usageMes) {
                        return response()->json([
                            'errors' => 'The given data was invalid.',
                            'message' => ['There is no any ID Jenis Vet on system at row ' . $count_row],
                        ], 422);
                    }
                }

                $total_data += 1;
                $count_row += 1;
            }
        }

        //process insert
        DB::beginTransaction();
        try {
            for ($i = 1; $i < count($src1); $i++) {

                $joinDate = Date::excelToDateTimeObject($src1[$i]['tanggal_join']);
                $joinDateFormatted = $joinDate->format('Y-m-d');

                $birthDate = Date::excelToDateTimeObject($src1[$i]['tanggal_lahir']);
                $birthDateFormatted = $birthDate->format('Y-m-d');

                $customerId = DB::table('customer')
                    ->insertGetId([
                        'memberNo' => trim($src1[$i]['nomor_member']),
                        'firstName' => trim($src1[$i]['nama_depan']),
                        'middleName' => trim($src1[$i]['nama_tengah']),
                        'lastName' => trim($src1[$i]['nama_akhir']),
                        'nickName' => trim($src1[$i]['nama_panggilan']),
                        'gender' => trim($src1[$i]['jenis_kelamin']),
                        'titleCustomerId' => trim($src1[$i]['id_gelar']),
                        'customerGroupId' => trim($src1[$i]['id_grup_pelanggan']),
                        'locationId' => trim($src1[$i]['id_lokasi']),
                        'notes' => trim($src1[$i]['catatan_tambahan']),
                        'joinDate' => $joinDateFormatted,
                        'typeId' => trim($src1[$i]['id_tipe_identitas']),
                        'numberId' => trim($src1[$i]['nomor_kartu_identitas']),
                        'occupationId' => trim($src1[$i]['id_pekerjaan']),
                        'birthDate' => $birthDateFormatted,
                        'referenceCustomerId' => trim($src1[$i]['id_referensi']),
                        'isReminderBooking' => trim($src1[$i]['pengingat_booking']),
                        'isReminderPayment' => trim($src1[$i]['pengingat_pembayaran']),
                        'isDeleted' => 0, // or another value based on your logic
                        'deletedBy' => null, // or fill as necessary
                        'deletedAt' => null, // or fill as necessary
                        'createdBy' => $request->user()->id, // Adjust as necessary
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                $resultVet = collect($src2)->where('id', $src1[$i]['id']);

                if ($resultVet) {
                    foreach ($resultVet as $value) {

                        $birthDate = Date::excelToDateTimeObject($value['tanggal_lahir']);
                        $birthDateFormatted = $birthDate->format('Y-m-d');

                        $bulan = null;
                        $tahun = null;

                        if (trim($value['bulan'])) {
                            $bulan = trim($value['bulan']);
                        }

                        if (trim($value['tahun'])) {
                            $tahun = trim($value['tahun']);
                        }

                        DB::table('customerPets')
                            ->insertGetId([
                                'customerId' => $customerId,
                                'petName' => trim($value['nama_vet']),
                                'petCategoryId' => trim($value['jenis_vet']),
                                'races' => trim($value['ras']),
                                'condition' => trim($value['kondisi']),
                                'color' => trim($value['warna']),
                                'petGender' => trim($value['jenis_kelamin']),
                                'isSteril' => trim($value['sudah_steril']),
                                'petMonth' => $bulan,
                                'petYear' => $tahun,
                                'dateOfBirth' => trim($birthDateFormatted),
                                'isDeleted' => 0, // or another value based on your logic
                                'deletedBy' => null, // or fill as necessary
                                'deletedAt' => null, // or fill as necessary
                                //'createdBy' => $request->user()->id, // Adjust as necessary
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                    }
                }

                $resultAddress = collect($src3)->where('id', $src1[$i]['id']);

                if ($resultAddress) {
                    foreach ($resultAddress as $value) {

                        DB::table('customerAddresses')
                            ->insertGetId([

                                'customerId' => $customerId,
                                'addressName' => trim($value['alamat_jalan']),
                                'additionalInfo' => trim($value['informasi_tambahan']),
                                'country' => trim('Indonesia'),
                                'provinceCode' => trim($value['kode_provinsi']),
                                'cityCode' => trim($value['kode_kota']),
                                'postalCode' => trim($value['kode_pos']),
                                'isPrimary' => trim($value['jadikan_sebagai_alamat_utama']),
                                'isDeleted' => 0, // or another value based on your logic
                                'deletedBy' => null, // or fill as necessary
                                'deletedAt' => null, // or fill as necessary
                                //'createdBy' => $request->user()->id, // Adjust as necessary
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                    }
                }

                //here
                $resultTelp = collect($src4)->where('id', $src1[$i]['id']);

                if ($resultTelp) {
                    foreach ($resultTelp as $value) {

                        DB::table('customerTelephones')
                            ->insertGetId([

                                'customerId' => $customerId,
                                'phoneNumber' => trim($value['nomor']),
                                'type' => trim($value['id_tipe']),
                                'usage' => trim($value['id_pemakaian']),
                                'isDeleted' => 0, // or another value based on your logic
                                'deletedBy' => null, // or fill as necessary
                                'deletedAt' => null, // or fill as necessary
                                //'createdBy' => $request->user()->id, // Adjust as necessary
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                    }
                }

                $resultEmail = collect($src5)->where('id', $src1[$i]['id']);

                if ($resultEmail) {
                    foreach ($resultEmail as $value) {

                        DB::table('customerEmails')
                            ->insertGetId([

                                'customerId' => $customerId,
                                'email' => trim($value['alamat_email']),
                                'usage' => trim($value['id_pemakaian']),
                                'isDeleted' => 0, // or another value based on your logic
                                'deletedBy' => null, // or fill as necessary
                                'deletedAt' => null, // or fill as necessary
                                //'createdBy' => $request->user()->id, // Adjust as necessary
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                    }
                }

                $resultMess = collect($src6)->where('id', $src1[$i]['id']);

                if ($resultMess) {
                    foreach ($resultMess as $value) {

                        DB::table('customerMessengers')
                            ->insertGetId([

                                'customerId' => $customerId,
                                'messengerNumber' => trim($value['nama_akun']),
                                'type' => trim($value['id_tipe']),
                                'usage' => trim($value['id_pemakaian']),
                                'isDeleted' => 0, // or another value based on your logic
                                'deletedBy' => null, // or fill as necessary
                                'deletedAt' => null, // or fill as necessary
                                //'createdBy' => $request->user()->id, // Adjust as necessary
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                    }
                }
            }

            ModelsImportCustomer::create([
                'fileName' => $filename,
                'totalData' => count($src1) - 1,
                'isDeleted' => 0, // or another value based on your logic
                'deletedBy' => null, // or fill as necessary
                'deletedAt' => null, // or fill as necessary
                'userUpdateId' => null, // or fill as necessary
                'created_at' => now(),
                'updated_at' => now(),
                'userId' => $request->user()->id,
            ]);

            DB::commit();

            return responseSuccess(count($src1) - 1, 'Insert Data Successful!');
        } catch (\Throwable $th) {
            DB::rollback();

            return response()->json([
                'message' => 'Failed',
                'errors' => $th,
            ]);
        }
    }

    private function isExcelSerialDate($value)
    {
        // Ensure the value is numeric and not a decimal (for dates without time)
        if (is_numeric($value)) {
            // Check if it's an integer and within the valid Excel serial date range
            if ($value >= 1 && $value <= 2958465 && $value == floor($value)) {
                return true; // It's a valid Excel serial date
            }
        }
        return false; // Not a valid Excel serial date
    }

    private function isValidDate($date)
    {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
